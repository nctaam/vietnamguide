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

function New-TestRecoveryBashFence {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$Body,

        [int]$StartLine = 10,

        [string]$Id = 'fence-bash-0001',

        [string]$SectionId = 'section-bash-0001'
    )

    [pscustomobject]@{
        Id = $Id
        SectionId = $SectionId
        Language = 'bash'
        RawInfo = 'bash'
        Body = $Body
        StartLine = $StartLine
        EndLine = $StartLine + (@($Body.Replace("`r`n", "`n").Replace("`r", "`n") -split "`n", -1).Count) + 1
    }
}

function Assert-ParserArrayEqual {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [object[]]$Actual,

        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [object[]]$Expected,

        [Parameter(Mandatory = $true)]
        [string]$Message
    )

    Assert-ParserEqual -Actual $Actual.Count -Expected $Expected.Count -Message "$Message Count mismatch."
    for ($index = 0; $index -lt $Expected.Count; $index++) {
        Assert-ParserEqual -Actual $Actual[$index] -Expected $Expected[$index] -Message "$Message Item $index mismatch."
    }
}

function Assert-RecoveryBashEventTexts {
    param(
        [Parameter(Mandatory = $true)]
        $Result,

        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [string[]]$Expected
    )

    $actual = @($Result.Events | ForEach-Object { $_.Text })
    Assert-ParserArrayEqual -Actual $actual -Expected $Expected -Message 'Bash event text mismatch.'
}

function New-TestRecoveryPowerShellFence {
    param(
        [Parameter(Mandatory = $true)]
        [AllowEmptyString()]
        [string]$Body,

        [int]$StartLine = 20,

        [string]$Id = 'fence-powershell',

        [string]$SectionId = 'section-powershell'
    )

    [pscustomobject][ordered]@{
        Id = $Id
        SectionId = $SectionId
        Language = 'powershell'
        RawInfo = 'powershell'
        Body = $Body
        StartLine = $StartLine
        EndLine = $StartLine + @($Body.Replace("`r`n", "`n").Replace("`r", "`n") -split "`n", -1).Count + 1
    }
}

$script:TestRecoveryPowerShellNativeCommandNames = @('powershell', 'pwsh', 'node', 'php', 'git', 'ssh', 'scp')

function Invoke-TestRecoveryPowerShellFenceParser {
    param(
        [Parameter(Mandatory = $true)]
        $Fence,

        [AllowEmptyCollection()]
        [string[]]$NativeCommandNames = $script:TestRecoveryPowerShellNativeCommandNames
    )

    ConvertFrom-RecoveryPowerShellFence -Fence $Fence -NativeCommandNames $NativeCommandNames
}

function Assert-RecoveryPowerShellEventCommands {
    param(
        [Parameter(Mandatory = $true)]
        $Result,

        [Parameter(Mandatory = $true)]
        [string[]]$Expected
    )

    Assert-ParserArrayEqual -Actual @($Result.Events | ForEach-Object { $_.NormalizedCommand }) -Expected $Expected -Message 'PowerShell normalized command mismatch.'
}

Add-ParserResult -Name 'Bash parser joins a split heredoc operator before consuming its body' -Test {
    $body = [string]::Join("`n", @('cat <\', '<EOF', 'BODY_MARKER', 'EOF', 'echo visible'))
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Split heredoc operator should be valid.'
    Assert-RecoveryBashEventTexts -Result $result -Expected @('cat <<EOF', 'echo visible')
}

Add-ParserResult -Name 'Bash parser does not continue a backslash after comment start' -Test {
    $body = [string]::Join("`n", @('echo ok # comment \', 'cat <<EOF', 'BODY_MARKER', 'EOF'))
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Comment backslash should remain literal comment text.'
    Assert-RecoveryBashEventTexts -Result $result -Expected @('echo ok # comment \', 'cat <<EOF')
}

Add-ParserResult -Name 'Bash parser rejects an unfinished unquoted continuation' -Test {
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body 'echo broken \' -StartLine 40)
    $diagnostic = @($result.Diagnostics)[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Unfinished continuation should be invalid.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('BASH_UNFINISHED_CONTINUATION')
    Assert-ParserEqual -Actual $diagnostic.Language -Expected 'bash' -Message 'Continuation diagnostic language mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceLine -Expected 41 -Message 'Continuation diagnostic source line mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceColumn -Expected 13 -Message 'Continuation diagnostic source column mismatch.'
    Assert-ParserEqual -Actual $diagnostic.FenceId -Expected 'fence-bash-0001' -Message 'Continuation diagnostic fence mismatch.'
}

Add-ParserResult -Name 'Bash parser treats exact three less-than characters as a here-string' -Test {
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body ': <<<EOF')

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Here-string should be valid.'
    Assert-RecoveryBashEventTexts -Result $result -Expected @(': <<<EOF')
}

Add-ParserResult -Name 'Bash parser rejects every long less-than redirection run' -Test {
    foreach ($command in @('cat <<<<EOF', 'cat <<<<<EOF')) {
        $body = [string]::Join("`n", @($command, 'echo hidden'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Long redirection '$command' should be invalid."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('BASH_AMBIGUOUS_REDIRECTION')
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'Ambiguous statement and later statements should not emit events.'
    }
}

Add-ParserResult -Name 'Bash parser skips shift operators inside supported arithmetic forms' -Test {
    foreach ($command in @(': $((1 << 2))', '((value << 1))')) {
        $body = [string]::Join("`n", @($command, 'echo visible'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message "Arithmetic command '$command' should be valid."
        Assert-RecoveryBashEventTexts -Result $result -Expected @($command, 'echo visible')
    }
}

Add-ParserResult -Name 'Bash parser rejects unclosed arithmetic forms' -Test {
    foreach ($command in @(': $((1 << 2)', '((value << 1)')) {
        $body = [string]::Join("`n", @('echo before', $command, 'echo hidden'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Arithmetic command '$command' should be invalid."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('BASH_INVALID_ARITHMETIC')
        Assert-RecoveryBashEventTexts -Result $result -Expected @('echo before')
    }
}

Add-ParserResult -Name 'Bash parser consumes multiple heredocs in FIFO order' -Test {
    $body = [string]::Join("`n", @("cat <<A <<'B'", 'BODY_A', 'A', 'BODY_B', 'B', 'echo visible'))
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'FIFO heredoc queue should be valid.'
    Assert-RecoveryBashEventTexts -Result $result -Expected @("cat <<A <<'B'", 'echo visible')
}

Add-ParserResult -Name 'Bash parser removes supported heredoc delimiter quoting and escapes' -Test {
    $cases = @(
        [pscustomobject]@{ Opener = 'cat <<EOF'; Body = @('BODY_MARKER', 'EOF') },
        [pscustomobject]@{ Opener = "cat <<'EOF'"; Body = @('BODY_MARKER', 'EOF') },
        [pscustomobject]@{ Opener = 'cat <<"EOF"'; Body = @('BODY_MARKER', 'EOF') },
        [pscustomobject]@{ Opener = 'cat <<E\OF'; Body = @('BODY_MARKER', 'EOF') },
        [pscustomobject]@{ Opener = 'cat <<-EOF'; Body = @("`tBODY_MARKER", "`tEOF") }
    )

    foreach ($case in $cases) {
        $body = [string]::Join("`n", @($case.Opener) + @($case.Body) + @('echo visible'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message "Heredoc opener '$($case.Opener)' should be valid."
        Assert-RecoveryBashEventTexts -Result $result -Expected @($case.Opener, 'echo visible')
    }
}

Add-ParserResult -Name 'Bash parser ignores heredoc text inside ordinary quoted strings' -Test {
    foreach ($command in @("printf '%s' '<<EOF'", 'printf "%s" "<<EOF"')) {
        $body = [string]::Join("`n", @($command, 'echo visible'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message "Quoted command '$command' should be valid."
        Assert-RecoveryBashEventTexts -Result $result -Expected @($command, 'echo visible')
    }
}

Add-ParserResult -Name 'Bash parser diagnoses unterminated ordinary quotes' -Test {
    $cases = @(
        [pscustomobject]@{ Command = "echo 'broken"; Code = 'BASH_UNTERMINATED_SINGLE_QUOTE'; Column = 6 },
        [pscustomobject]@{ Command = 'echo "broken'; Code = 'BASH_UNTERMINATED_DOUBLE_QUOTE'; Column = 6 }
    )

    foreach ($case in $cases) {
        $body = [string]::Join("`n", @('echo before', $case.Command, 'echo hidden'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body -StartLine 20)
        $diagnostic = @($result.Diagnostics)[0]

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Quote case '$($case.Code)' should be invalid."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @($case.Code)
        Assert-RecoveryBashEventTexts -Result $result -Expected @('echo before')
        Assert-ParserEqual -Actual $diagnostic.SourceLine -Expected 22 -Message 'Quote diagnostic source line mismatch.'
        Assert-ParserEqual -Actual $diagnostic.SourceColumn -Expected $case.Column -Message 'Quote diagnostic source column mismatch.'
    }
}

Add-ParserResult -Name 'Bash parser rejects a heredoc queue missing any FIFO terminator' -Test {
    $body = [string]::Join("`n", @('echo before', 'cat <<A <<B', 'BODY_A', 'A', 'BODY_B'))
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body -StartLine 30)
    $diagnostic = @($result.Diagnostics)[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Unbalanced heredoc queue should be invalid.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('BASH_UNBALANCED_HEREDOC_QUEUE')
    Assert-RecoveryBashEventTexts -Result $result -Expected @('echo before', 'cat <<A <<B')
    Assert-ParserEqual -Actual $diagnostic.SourceLine -Expected 32 -Message 'Heredoc queue diagnostic source line mismatch.'
    Assert-ParserEqual -Actual $diagnostic.SourceColumn -Expected 5 -Message 'Heredoc queue diagnostic source column mismatch.'
}

Add-ParserResult -Name 'Bash parser applies token boundaries when starting comments' -Test {
    $body = [string]::Join("`n", @(
        '# pure comment',
        '  # indented comment',
        'echo whitespace # comment <<EOF \',
        'echo escaped \#value',
        'echo word#fragment',
        'echo control;# comment \',
        'echo visible'
    ))
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Comment token boundaries should be valid.'
    Assert-RecoveryBashEventTexts -Result $result -Expected @(
        'echo whitespace # comment <<EOF \',
        'echo escaped \#value',
        'echo word#fragment',
        'echo control;# comment \',
        'echo visible'
    )
}

Add-ParserResult -Name 'Bash parser preserves word token state across continuations' -Test {
    $body = [string]::Join("`n", @('echo word\', '#fragment\', 'tail'))
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body -StartLine 60 -Id 'fence-cross-boundary' -SectionId 'section-cross-boundary')
    $events = @($result.Events)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Cross-boundary word fragment should be valid.'
    Assert-ParserEqual -Actual @($result.Diagnostics).Count -Expected 0 -Message 'Cross-boundary word fragment should have no diagnostics.'
    Assert-RecoveryBashEventTexts -Result $result -Expected @('echo word#fragmenttail')
    Assert-ParserEqual -Actual $events.Count -Expected 1 -Message 'Cross-boundary word fragment event count mismatch.'
    Assert-ParserEqual -Actual $events[0].SourceLine -Expected 61 -Message 'Cross-boundary event source line mismatch.'
    Assert-ParserEqual -Actual $events[0].SourceColumn -Expected 1 -Message 'Cross-boundary event source column mismatch.'
    Assert-ParserEqual -Actual $events[0].SectionId -Expected 'section-cross-boundary' -Message 'Cross-boundary event section mismatch.'
    Assert-ParserEqual -Actual $events[0].FenceId -Expected 'fence-cross-boundary' -Message 'Cross-boundary event fence mismatch.'
    Assert-ParserEqual -Actual $events[0].StatementId -Expected 'fence-cross-boundary-statement-0001' -Message 'Cross-boundary statement identifier mismatch.'
}

Add-ParserResult -Name 'Bash parser emits deterministic source metadata and statement identifiers' -Test {
    $body = [string]::Join("`n", @('  echo first', '', "`techo second"))
    $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body -StartLine 100 -Id 'fence-custom' -SectionId 'section-custom')
    $events = @($result.Events)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Metadata case should be valid.'
    Assert-ParserEqual -Actual $events.Count -Expected 2 -Message 'Metadata event count mismatch.'
    Assert-ParserEqual -Actual $events[0].Kind -Expected 'command' -Message 'First event kind mismatch.'
    Assert-ParserEqual -Actual $events[0].Language -Expected 'bash' -Message 'First event language mismatch.'
    Assert-ParserEqual -Actual $events[0].SourceLine -Expected 101 -Message 'First event source line mismatch.'
    Assert-ParserEqual -Actual $events[0].SourceColumn -Expected 3 -Message 'First event source column mismatch.'
    Assert-ParserEqual -Actual $events[0].SectionId -Expected 'section-custom' -Message 'First event section mismatch.'
    Assert-ParserEqual -Actual $events[0].FenceId -Expected 'fence-custom' -Message 'First event fence mismatch.'
    Assert-ParserEqual -Actual $events[0].StatementId -Expected 'fence-custom-statement-0001' -Message 'First statement identifier mismatch.'
    Assert-ParserEqual -Actual $events[0].NormalizedCommand -Expected $null -Message 'First normalized command should be null.'
    Assert-ParserEqual -Actual $events[0].Metadata.GetType().FullName -Expected 'System.Collections.Hashtable' -Message 'First metadata should be a hashtable.'
    Assert-ParserEqual -Actual $events[1].SourceLine -Expected 103 -Message 'Second event source line mismatch.'
    Assert-ParserEqual -Actual $events[1].SourceColumn -Expected 2 -Message 'Second event source column mismatch.'
    Assert-ParserEqual -Actual $events[1].StatementId -Expected 'fence-custom-statement-0002' -Message 'Second statement identifier mismatch.'
}

Add-ParserResult -Name 'Bash parser handles empty and trailing physical body lines deterministically' -Test {
    $cases = @(
        [pscustomobject]@{ Body = ''; Expected = @(); Lines = @() },
        [pscustomobject]@{ Body = "`n"; Expected = @(); Lines = @() },
        [pscustomobject]@{ Body = "echo only`n"; Expected = @('echo only'); Lines = @(11) },
        [pscustomobject]@{ Body = "echo one`r`n`r`necho two`r"; Expected = @('echo one', 'echo two'); Lines = @(11, 13) }
    )

    foreach ($case in $cases) {
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $case.Body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Empty and trailing body case should be valid.'
        Assert-RecoveryBashEventTexts -Result $result -Expected @($case.Expected)
        Assert-ParserArrayEqual -Actual @($result.Events | ForEach-Object { $_.SourceLine }) -Expected @($case.Lines) -Message 'Normalized Bash source line mismatch.'
    }
}

Add-ParserResult -Name 'Bash parser rejects comment-start heredoc delimiters and preserves authored delimiter words' -Test {
    $invalidCases = @(
        [pscustomobject]@{ Name = 'spaced comment'; Lines = @('cat << #EOF', 'BODY', '#EOF', 'echo hidden') },
        [pscustomobject]@{ Name = 'adjacent comment'; Lines = @('cat <<#EOF', 'BODY', '#EOF', 'echo hidden') },
        [pscustomobject]@{ Name = 'continued comment'; Lines = @('cat <<\', '#EOF', 'BODY', '#EOF', 'echo hidden') }
    )

    foreach ($case in $invalidCases) {
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body ([string]::Join("`n", $case.Lines)))

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Heredoc $($case.Name) delimiter should be invalid."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('BASH_AMBIGUOUS_REDIRECTION')
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Heredoc $($case.Name) delimiter should stop all event emission."
    }

    $validCases = @(
        [pscustomobject]@{ Opener = 'cat <<\#EOF'; Delimiter = '#EOF' },
        [pscustomobject]@{ Opener = "cat <<'#EOF'"; Delimiter = '#EOF' },
        [pscustomobject]@{ Opener = 'cat <<"#EOF"'; Delimiter = '#EOF' },
        [pscustomobject]@{ Opener = 'cat <<E#OF'; Delimiter = 'E#OF' },
        [pscustomobject]@{ Opener = "cat <<'E'#OF"; Delimiter = 'E#OF' }
    )

    foreach ($case in $validCases) {
        $body = [string]::Join("`n", @($case.Opener, 'BODY', $case.Delimiter, 'echo visible'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message "Heredoc opener '$($case.Opener)' should be valid."
        Assert-RecoveryBashEventTexts -Result $result -Expected @($case.Opener, 'echo visible')
    }
}

Add-ParserResult -Name 'Bash parser validates arithmetic expansion inside double quotes' -Test {
    $validCommands = @(
        'echo "$((1 << 2))"',
        'echo "\$((1 << 2)"',
        'echo ''$((1 << 2)'''
    )

    foreach ($command in $validCommands) {
        $body = [string]::Join("`n", @($command, 'echo visible'))
        $result = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message "Quoted arithmetic command '$command' should be valid."
        Assert-RecoveryBashEventTexts -Result $result -Expected @($command, 'echo visible')
    }

    $invalidCommand = 'echo "$((1 << 2)"'
    $invalidBody = [string]::Join("`n", @($invalidCommand, 'echo hidden'))
    $invalidResult = ConvertFrom-RecoveryBashFence -Fence (New-TestRecoveryBashFence -Body $invalidBody)

    Assert-ParserEqual -Actual $invalidResult.IsValid -Expected $false -Message 'Malformed double-quoted arithmetic expansion should be invalid.'
    Assert-ParserDiagnosticCodes -Diagnostics @($invalidResult.Diagnostics) -Expected @('BASH_INVALID_ARITHMETIC')
    Assert-ParserEqual -Actual @($invalidResult.Events).Count -Expected 0 -Message 'Malformed double-quoted arithmetic should stop later event emission.'
}

Add-ParserResult -Name 'PowerShell parser requires an immediate native guard' -Test {
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body 'git status' -StartLine 40)

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Unguarded git should be invalid.'
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'Unguarded git should not emit an event.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_GUARD_MISSING')
    Assert-ParserEqual -Actual $result.Diagnostics[0].SourceLine -Expected 41 -Message 'Unguarded git diagnostic source line mismatch.'
    Assert-ParserEqual -Actual $result.Diagnostics[0].SourceColumn -Expected 1 -Message 'Unguarded git diagnostic source column mismatch.'
}

Add-ParserResult -Name 'PowerShell parser normalizes guarded scp executable names' -Test {
    $body = [string]::Join("`n", @('scp.exe x host:y', 'if ($LASTEXITCODE -ne 0) { throw "scp failed" }'))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Guarded scp.exe should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $result -Expected @('scp')
    Assert-ParserEqual -Actual $result.Events[0].Text -Expected 'scp.exe x host:y' -Message 'scp event text mismatch.'
}

Add-ParserResult -Name 'PowerShell parser rejects unsupported native wrappers' -Test {
    $commands = @(
        'git.cmd status',
        'git.bat status',
        'git.com status',
        'cmd.exe /c git status',
        "& 'C:\tools\git.cmd' status",
        "& 'C:\tools\git.bat' status",
        "& 'C:\tools\git.com' status",
        "& 'C:\Windows\System32\cmd.exe' /c git status"
    )

    foreach ($command in $commands) {
        $body = [string]::Join("`n", @($command, 'if ($LASTEXITCODE -ne 0) { throw "failed" }'))
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Wrapper '$command' should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Wrapper '$command' should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_UNSUPPORTED_NATIVE_WRAPPER')
    }
}

Add-ParserResult -Name 'PowerShell parser rejects a zero exit guard' -Test {
    $body = [string]::Join("`n", @('git status', 'if ($LASTEXITCODE -ne 0) { exit 0 }'))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'exit 0 guard should be invalid.'
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'exit 0 guard should not emit an event.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_GUARD_NONBLOCKING')
}

Add-ParserResult -Name 'PowerShell parser accepts effective standard guard blockers' -Test {
    $guards = @(
        'if ($LASTEXITCODE -ne 0) { exit 1 }',
        'if ($LASTEXITCODE -ne 0) { exit -1 }',
        'if ($LASTEXITCODE -ne 0) { throw "git failed" }'
    )

    foreach ($guard in $guards) {
        $body = [string]::Join("`n", @('git status', $guard))
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message "Guard '$guard' should be valid."
        Assert-RecoveryPowerShellEventCommands -Result $result -Expected @('git')
    }
}

Add-ParserResult -Name 'PowerShell parser rejects guards containing traps' -Test {
    $guards = @(
        'if ($LASTEXITCODE -ne 0) { trap { continue }; throw ''failed'' }',
        'if ($LASTEXITCODE -ne 0) { trap { continue }; exit 1 }'
    )

    foreach ($guard in $guards) {
        $body = [string]::Join("`n", @('git status', $guard))
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Guard '$guard' should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Guard '$guard' should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_GUARD_NONBLOCKING')
    }
}

Add-ParserResult -Name 'PowerShell parser accepts a captured native exit guard' -Test {
    $body = [string]::Join("`n", @(
        'git status',
        '$gitExit = $LASTEXITCODE',
        'if ($gitExit -ne 0) { exit $gitExit }'
    ))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Captured git exit guard should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $result -Expected @('git')
}

Add-ParserResult -Name 'PowerShell parser rejects unsafe captured exit targets' -Test {
    $cases = @(
        [pscustomobject]@{ Name = 'null'; Capture = '$null = $LASTEXITCODE'; Condition = '$null' },
        [pscustomobject]@{ Name = 'true'; Capture = '$true = $LASTEXITCODE'; Condition = '$true' },
        [pscustomobject]@{ Name = 'false'; Capture = '$false = $LASTEXITCODE'; Condition = '$false' },
        [pscustomobject]@{ Name = 'scoped'; Capture = '$global:gitExit = $LASTEXITCODE'; Condition = '$global:gitExit' },
        [pscustomobject]@{ Name = 'drive'; Capture = '$env:gitExit = $LASTEXITCODE'; Condition = '$env:gitExit' },
        [pscustomobject]@{ Name = 'property'; Capture = '$holder.ExitCode = $LASTEXITCODE'; Condition = '$holder.ExitCode' },
        [pscustomobject]@{ Name = 'tuple'; Capture = '$firstExit, $secondExit = $LASTEXITCODE'; Condition = '$firstExit' },
        [pscustomobject]@{ Name = 'compound'; Capture = '$gitExit += $LASTEXITCODE'; Condition = '$gitExit' }
    )

    foreach ($case in $cases) {
        $body = [string]::Join("`n", @(
            'git status',
            $case.Capture,
            "if ($($case.Condition) -ne 0) { throw 'failed' }"
        ))
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Captured target '$($case.Name)' should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Captured target '$($case.Name)' should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_GUARD_MISSING')
    }
}

Add-ParserResult -Name 'PowerShell parser classifies malformed captured guards deterministically' -Test {
    $cases = @(
        [pscustomobject]@{ Name = 'wrong variable'; Lines = @('git status', '$gitExit = $LASTEXITCODE', 'if ($otherExit -ne 0) { throw "failed" }'); Code = 'PS_NATIVE_GUARD_MISSING' },
        [pscustomobject]@{ Name = 'wrong condition'; Lines = @('git status', '$gitExit = $LASTEXITCODE', 'if ($gitExit -eq 0) { throw "failed" }'); Code = 'PS_NATIVE_GUARD_MISSING' },
        [pscustomobject]@{ Name = 'intervening statement'; Lines = @('git status', '$gitExit = $LASTEXITCODE', 'Write-Host waiting', 'if ($gitExit -ne 0) { throw "failed" }'); Code = 'PS_NATIVE_GUARD_MISSING' },
        [pscustomobject]@{ Name = 'zero exit'; Lines = @('git status', '$gitExit = $LASTEXITCODE', 'if ($gitExit -ne 0) { exit 0 }'); Code = 'PS_NATIVE_GUARD_NONBLOCKING' },
        [pscustomobject]@{ Name = 'unknown exit'; Lines = @('git status', '$gitExit = $LASTEXITCODE', 'if ($gitExit -ne 0) { exit $otherExit }'); Code = 'PS_NATIVE_GUARD_NONBLOCKING' }
    )

    foreach ($case in $cases) {
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body ([string]::Join("`n", $case.Lines)))

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Captured guard $($case.Name) should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Captured guard $($case.Name) should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @($case.Code)
    }
}

Add-ParserResult -Name 'PowerShell parser restricts dynamic native invocation' -Test {
    $unknownBody = [string]::Join("`n", @('& $UnknownExecutable --version', 'if ($LASTEXITCODE -ne 0) { throw "failed" }'))
    $unknownResult = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $unknownBody)
    Assert-ParserDiagnosticCodes -Diagnostics @($unknownResult.Diagnostics) -Expected @('PS_DYNAMIC_NATIVE_UNSUPPORTED')
    Assert-ParserEqual -Actual @($unknownResult.Events).Count -Expected 0 -Message 'Unknown dynamic command should not emit an event.'

    $phpBody = [string]::Join("`n", @('$PhpExecutable = ''php''', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw "php failed" }'))
    $phpResult = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $phpBody)
    Assert-ParserEqual -Actual $phpResult.IsValid -Expected $true -Message 'Configured PhpExecutable command should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $phpResult -Expected @('php')

    $excludedPhpResult = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $phpBody) -NativeCommandNames @('git')
    Assert-ParserDiagnosticCodes -Diagnostics @($excludedPhpResult.Diagnostics) -Expected @('PS_DYNAMIC_NATIVE_UNSUPPORTED')
    Assert-ParserEqual -Actual @($excludedPhpResult.Events).Count -Expected 0 -Message 'Excluded PhpExecutable command should not emit an event.'
}

Add-ParserResult -Name 'PowerShell parser requires a static PhpExecutable assignment' -Test {
    $cases = @(
        [pscustomobject]@{ Name = 'cmd wrapper'; Lines = @('$PhpExecutable = ''cmd.exe''', '& $PhpExecutable /c exit 0', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }'); Codes = @('PS_UNSUPPORTED_NATIVE_WRAPPER'); Commands = @() },
        [pscustomobject]@{ Name = 'known git'; Lines = @('$PhpExecutable = ''git''', '& $PhpExecutable status', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }'); Codes = @(); Commands = @('git') },
        [pscustomobject]@{ Name = 'environment value'; Lines = @('$PhpExecutable = $env:ComSpec', '& $PhpExecutable /c exit 0', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }'); Codes = @('PS_DYNAMIC_NATIVE_UNSUPPORTED'); Commands = @() },
        [pscustomobject]@{ Name = 'full cmd path'; Lines = @('$PhpExecutable = ''C:\Windows\System32\cmd.exe''', '& $PhpExecutable /c exit 0', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }'); Codes = @('PS_UNSUPPORTED_NATIVE_WRAPPER'); Commands = @() },
        [pscustomobject]@{ Name = 'no assignment'; Lines = @('& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }'); Codes = @('PS_DYNAMIC_NATIVE_UNSUPPORTED'); Commands = @() },
        [pscustomobject]@{ Name = 'nonliteral assignment'; Lines = @('$PhpExecutable = Get-Thing', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }'); Codes = @('PS_DYNAMIC_NATIVE_UNSUPPORTED'); Commands = @() }
    )

    foreach ($case in $cases) {
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body ([string]::Join("`n", $case.Lines)))

        Assert-ParserEqual -Actual $result.IsValid -Expected ($case.Codes.Count -eq 0) -Message "PhpExecutable case '$($case.Name)' validity mismatch."
        if ($case.Codes.Count -eq 0) {
            Assert-ParserEqual -Actual @($result.Diagnostics).Count -Expected 0 -Message "PhpExecutable case '$($case.Name)' diagnostic count mismatch."
        }
        else {
            Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected $case.Codes
        }
        if ($case.Commands.Count -eq 0) {
            Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "PhpExecutable case '$($case.Name)' event count mismatch."
        }
        else {
            Assert-RecoveryPowerShellEventCommands -Result $result -Expected $case.Commands
        }
    }

    $staticPathBody = [string]::Join("`n", @('$PhpExecutable = ''C:\tools\php.exe''', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''php failed'' }'))
    $staticPathResult = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $staticPathBody)
    Assert-ParserEqual -Actual $staticPathResult.IsValid -Expected $true -Message 'Static php.exe path should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $staticPathResult -Expected @('php')
}

Add-ParserResult -Name 'PowerShell parser rejects nondominating PhpExecutable assignments' -Test {
    $cases = @(
        [pscustomobject]@{ Name = 'false conditional'; Body = [string]::Join("`n", @('if ($false) { $PhpExecutable = ''php.exe'' }', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }')) },
        [pscustomobject]@{ Name = 'uncalled function'; Body = [string]::Join("`n", @('function Set-PhpExecutable { $PhpExecutable = ''php.exe'' }', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }')) },
        [pscustomobject]@{ Name = 'array expression'; Body = [string]::Join("`n", @('$values = @(', '    $PhpExecutable = ''php.exe''', ')', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }')) },
        [pscustomobject]@{ Name = 'try branch'; Body = [string]::Join("`n", @('try { $PhpExecutable = ''php.exe'' } finally { Write-Host done }', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }')) },
        [pscustomobject]@{ Name = 'nested rebind'; Body = [string]::Join("`n", @('$PhpExecutable = ''php''', 'if ($false) { $PhpExecutable = ''cmd.exe'' }', '& $PhpExecutable -v', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }')) }
    )

    foreach ($case in $cases) {
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $case.Body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "PhpExecutable $($case.Name) assignment should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "PhpExecutable $($case.Name) assignment should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_DYNAMIC_NATIVE_UNSUPPORTED')
    }
}

Add-ParserResult -Name 'PowerShell parser has no default native command policy' -Test {
    $body = [string]::Join("`n", @('git status', 'if ($LASTEXITCODE -ne 0) { throw ''failed'' }'))
    $fence = New-TestRecoveryPowerShellFence -Body $body
    $missingResult = RecoveryParser\ConvertFrom-RecoveryPowerShellFence -Fence $fence
    $emptyResult = RecoveryParser\ConvertFrom-RecoveryPowerShellFence -Fence $fence -NativeCommandNames @()

    foreach ($result in @($missingResult, $emptyResult)) {
        Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Policy-free parser result should remain structurally valid.'
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'Missing or empty native configuration should emit no events.'
        Assert-ParserEqual -Actual @($result.Diagnostics).Count -Expected 0 -Message 'Missing or empty native configuration should emit no diagnostics.'
    }
}

Add-ParserResult -Name 'PowerShell parser reports AST parse errors without events' -Test {
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body "git status`nif (`$LASTEXITCODE -ne 0) {")

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Malformed PowerShell should be invalid.'
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'Malformed PowerShell should not emit events.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_PARSE_ERROR')
    Assert-ParserEqual -Actual $result.Diagnostics[0].Language -Expected 'powershell' -Message 'Parse diagnostic language mismatch.'
    Assert-ParserEqual -Actual $result.Diagnostics[0].FenceId -Expected 'fence-powershell' -Message 'Parse diagnostic fence identifier mismatch.'
}

Add-ParserResult -Name 'PowerShell parser rejects nested or piped native statements' -Test {
    $commands = @(
        '$value = "$(git status)"',
        'Write-Host (git status)',
        'git status | Out-String'
    )

    foreach ($command in $commands) {
        $body = [string]::Join("`n", @($command, 'if ($LASTEXITCODE -ne 0) { throw "failed" }'))
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Nested command '$command' should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Nested command '$command' should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_STATEMENT_AMBIGUOUS')
    }
}

Add-ParserResult -Name 'PowerShell parser rejects native commands nested in array expressions' -Test {
    $body = [string]::Join("`n", @(
        '$values = @(',
        '    git status',
        '    if ($LASTEXITCODE -ne 0) { throw ''failed'' }',
        ')'
    ))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Array-nested native command should be invalid.'
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'Array-nested native command should not emit an event.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_STATEMENT_AMBIGUOUS')
}

Add-ParserResult -Name 'PowerShell parser rejects guards swallowed by enclosing trap or catch' -Test {
    $invalidCases = @(
        [pscustomobject]@{
            Name = 'enclosing trap'
            Body = [string]::Join("`n", @(
                'trap { continue }',
                'git status',
                'if ($LASTEXITCODE -ne 0) { throw ''failed'' }',
                'Write-Host TRAP_AFTER'
            ))
        },
        [pscustomobject]@{
            Name = 'try catch'
            Body = [string]::Join("`n", @(
                'try {',
                '    git status',
                '    if ($LASTEXITCODE -ne 0) { throw ''failed'' }',
                '} catch {',
                '    Write-Host TRY_CAUGHT',
                '}',
                'Write-Host TRY_AFTER'
            ))
        }
    )

    foreach ($case in $invalidCases) {
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $case.Body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Enclosing $($case.Name) should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Enclosing $($case.Name) should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_GUARD_NONBLOCKING')
    }

    $finallyBody = [string]::Join("`n", @(
        'try {',
        '    git status',
        '    if ($LASTEXITCODE -ne 0) { throw ''failed'' }',
        '} finally {',
        '    Write-Host TRY_FINALLY',
        '}'
    ))
    $finallyResult = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $finallyBody)
    Assert-ParserEqual -Actual $finallyResult.IsValid -Expected $true -Message 'Try/finally without catch should remain valid.'
    Assert-RecoveryPowerShellEventCommands -Result $finallyResult -Expected @('git')
}

Add-ParserResult -Name 'PowerShell parser rejects same-line intervening statements' -Test {
    $body = 'git status; Write-Host waiting; if ($LASTEXITCODE -ne 0) { throw "failed" }'
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message 'Same-line intervening statement should be invalid.'
    Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_GUARD_MISSING')
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'Same-line intervening statement should not emit an event.'
}

Add-ParserResult -Name 'PowerShell parser normalizes powershell hosts and requires guards' -Test {
    $validBody = [string]::Join("`n", @(
        'powershell.exe -NoProfile -Command "exit 0"',
        'if ($LASTEXITCODE -ne 0) { throw "powershell failed" }',
        'pwsh -NoProfile -Command "exit 0"',
        'if ($LASTEXITCODE -ne 0) { throw "pwsh failed" }'
    ))
    $validResult = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $validBody)
    Assert-ParserEqual -Actual $validResult.IsValid -Expected $true -Message 'Guarded PowerShell hosts should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $validResult -Expected @('powershell', 'pwsh')

    foreach ($command in @('powershell.exe -NoProfile', 'pwsh -NoProfile')) {
        $missingResult = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $command)
        Assert-ParserDiagnosticCodes -Diagnostics @($missingResult.Diagnostics) -Expected @('PS_NATIVE_GUARD_MISSING')
        Assert-ParserEqual -Actual @($missingResult.Events).Count -Expected 0 -Message "Unguarded host '$command' should not emit an event."
    }
}

Add-ParserResult -Name 'PowerShell parser allows a guarded git assignment capture' -Test {
    $body = [string]::Join("`n", @('$GitStatus = git status --porcelain', 'if ($LASTEXITCODE -ne 0) { throw "git failed" }'))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Git assignment capture should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $result -Expected @('git')
    Assert-ParserEqual -Actual $result.Events[0].Text -Expected 'git status --porcelain' -Message 'Git assignment event text mismatch.'
}

Add-ParserResult -Name 'PowerShell parser rejects non-simple git assignment targets and operators' -Test {
    $assignments = @(
        '$x += git status',
        '$x.Property = git status',
        '$x, $y = git status',
        '$x += (git rev-parse HEAD).Trim()'
    )

    foreach ($assignment in $assignments) {
        $body = [string]::Join("`n", @($assignment, 'if ($LASTEXITCODE -ne 0) { throw ''git failed'' }'))
        $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

        Assert-ParserEqual -Actual $result.IsValid -Expected $false -Message "Git assignment '$assignment' should be invalid."
        Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message "Git assignment '$assignment' should not emit an event."
        Assert-ParserDiagnosticCodes -Diagnostics @($result.Diagnostics) -Expected @('PS_NATIVE_STATEMENT_AMBIGUOUS')
    }
}

Add-ParserResult -Name 'PowerShell parser allows the authored trimmed git revision capture' -Test {
    $body = [string]::Join("`n", @('$RecoveryBaseSha = (git rev-parse HEAD).Trim()', 'if ($LASTEXITCODE -ne 0) { throw "git failed" }'))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Trimmed git revision capture should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $result -Expected @('git')
    Assert-ParserEqual -Actual $result.Events[0].Text -Expected 'git rev-parse HEAD' -Message 'Trimmed git event text mismatch.'
}

Add-ParserResult -Name 'PowerShell parser allows the authored git tri-state guard' -Test {
    $body = [string]::Join("`n", @(
        'git show-ref --verify --quiet refs/heads/master',
        '$masterExit = $LASTEXITCODE',
        'if ($masterExit -eq 0) {',
        '    Write-Host "master exists"',
        '}',
        'elseif ($masterExit -eq 1) {',
        '    Write-Host "master missing"',
        '}',
        'else {',
        '    throw "git show-ref failed"',
        '}'
    ))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body)

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Authored tri-state guard should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $result -Expected @('git')
}

Add-ParserResult -Name 'PowerShell parser emits exact normalized event metadata' -Test {
    $body = '  & ''C:\tools\git.exe'' status ' + [char]96 + "`r`n" + '    --porcelain' + "`r`n" + 'if ($LASTEXITCODE -ne 0) { throw "git failed" }'
    $fence = New-TestRecoveryPowerShellFence -Body $body -StartLine 100 -Id 'fence-custom' -SectionId 'section-custom'
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence $fence
    $event = $result.Events[0]

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Metadata case should be valid.'
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 1 -Message 'Metadata event count mismatch.'
    Assert-ParserProperties -Value $event -Expected @('Kind', 'Language', 'Text', 'NormalizedCommand', 'SourceLine', 'SourceColumn', 'SectionId', 'FenceId', 'StatementId', 'Metadata')
    Assert-ParserEqual -Actual $event.Kind -Expected 'command' -Message 'PowerShell event kind mismatch.'
    Assert-ParserEqual -Actual $event.Language -Expected 'powershell' -Message 'PowerShell event language mismatch.'
    Assert-ParserEqual -Actual $event.Text -Expected ("& 'C:\tools\git.exe' status " + [char]96 + "`n" + '    --porcelain') -Message 'PowerShell event text normalization mismatch.'
    Assert-ParserEqual -Actual $event.NormalizedCommand -Expected 'git' -Message 'PowerShell event normalized command mismatch.'
    Assert-ParserEqual -Actual $event.SourceLine -Expected 101 -Message 'PowerShell event source line mismatch.'
    Assert-ParserEqual -Actual $event.SourceColumn -Expected 3 -Message 'PowerShell event source column mismatch.'
    Assert-ParserEqual -Actual $event.SectionId -Expected 'section-custom' -Message 'PowerShell event section identifier mismatch.'
    Assert-ParserEqual -Actual $event.FenceId -Expected 'fence-custom' -Message 'PowerShell event fence identifier mismatch.'
    Assert-ParserEqual -Actual $event.StatementId -Expected 'fence-custom-statement-0001' -Message 'PowerShell event statement identifier mismatch.'
    Assert-ParserEqual -Actual $event.Metadata.GetType().FullName -Expected 'System.Collections.Hashtable' -Message 'PowerShell event metadata should be a hashtable.'
    Assert-ParserEqual -Actual $event.Metadata.Count -Expected 0 -Message 'PowerShell event metadata should be empty.'
}

Add-ParserResult -Name 'PowerShell parser preserves a null fence section identifier' -Test {
    $body = [string]::Join("`n", @('git status', 'if ($LASTEXITCODE -ne 0) { throw ''git failed'' }'))
    $fence = New-TestRecoveryPowerShellFence -Body $body
    $fence.SectionId = $null
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence $fence

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Null section identifier case should be valid.'
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 1 -Message 'Null section identifier event count mismatch.'
    Assert-ParserEqual -Actual $result.Events[0].SectionId -Expected $null -Message 'PowerShell event should preserve a null section identifier.'
}

Add-ParserResult -Name 'PowerShell parser emits multiple valid native commands in order' -Test {
    $body = [string]::Join("`n", @(
        'git status',
        'if ($LASTEXITCODE -ne 0) { throw "git failed" }',
        'scp x host:y',
        'if ($LASTEXITCODE -ne 0) { exit 1 }'
    ))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body -Id 'fence-order')

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Multiple guarded commands should be valid.'
    Assert-RecoveryPowerShellEventCommands -Result $result -Expected @('git', 'scp')
    Assert-ParserArrayEqual -Actual @($result.Events | ForEach-Object { $_.StatementId }) -Expected @('fence-order-statement-0001', 'fence-order-statement-0002') -Message 'PowerShell statement order mismatch.'
    Assert-ParserArrayEqual -Actual @($result.Events | ForEach-Object { $_.SourceLine }) -Expected @(21, 23) -Message 'PowerShell event source line order mismatch.'
}

Add-ParserResult -Name 'PowerShell parser ignores unknown ordinary commands and excluded natives' -Test {
    $body = [string]::Join("`n", @('Write-Host hello', 'Invoke-CustomThing', 'git status'))
    $result = Invoke-TestRecoveryPowerShellFenceParser -Fence (New-TestRecoveryPowerShellFence -Body $body) -NativeCommandNames @('scp')

    Assert-ParserEqual -Actual $result.IsValid -Expected $true -Message 'Unknown ordinary commands should be ignored.'
    Assert-ParserEqual -Actual @($result.Events).Count -Expected 0 -Message 'Unknown ordinary commands should not emit events.'
    Assert-ParserEqual -Actual @($result.Diagnostics).Count -Expected 0 -Message 'Unknown ordinary commands should not emit diagnostics.'
}

$results | Format-Table -AutoSize | Out-Host

$failed = @($results | Where-Object { -not $_.Passed })
if ($failed.Count -gt 0) {
    throw "Recovery parser verification failed: $($failed.Count)/$($results.Count) failed."
}

Write-Host "Recovery parser verification passed: $($results.Count)/$($results.Count)"

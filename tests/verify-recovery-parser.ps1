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
        $Actual,

        [Parameter(Mandatory = $true)]
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

Add-ParserResult -Name 'Diagnostic constructor returns the typed diagnostic contract' -Test {
    $diagnostic = New-RecoveryParserDiagnostic -Code 'RP001' -Message 'Example diagnostic' -Language 'powershell' -SourceLine 12 -SourceColumn 4 -FenceId 'fence-1'

    Assert-ParserProperties -Value $diagnostic -Expected @('Code', 'Message', 'Language', 'SourceLine', 'SourceColumn', 'FenceId')
    Assert-ParserEqual -Actual $diagnostic.Code -Expected 'RP001' -Message 'Diagnostic code mismatch.'
}

Add-ParserResult -Name 'Executable event constructor returns the typed event contract' -Test {
    $event = New-RecoveryExecutableEvent -Kind 'Command' -Language 'powershell' -Text 'Get-Date' -NormalizedCommand 'Get-Date' -SourceLine 8 -SourceColumn 1 -SectionId 'section-1' -FenceId 'fence-1' -StatementId 'statement-1' -Metadata @{ Source = 'test' }

    Assert-ParserProperties -Value $event -Expected @('Kind', 'Language', 'Text', 'NormalizedCommand', 'SourceLine', 'SourceColumn', 'SectionId', 'FenceId', 'StatementId', 'Metadata')
    Assert-ParserEqual -Actual $event.Kind -Expected 'Command' -Message 'Event kind mismatch.'
}

Add-ParserResult -Name 'Parse result filters null entries and derives validity from diagnostics' -Test {
    $event = New-RecoveryExecutableEvent -Kind 'Command' -Language 'powershell' -Text 'Get-Date' -NormalizedCommand 'Get-Date' -SourceLine 8 -SourceColumn 1 -SectionId 'section-1' -FenceId 'fence-1' -StatementId 'statement-1' -Metadata @{}
    $diagnostic = New-RecoveryParserDiagnostic -Code 'RP001' -Message 'Example diagnostic' -Language 'powershell' -SourceLine 12 -SourceColumn 4 -FenceId 'fence-1'
    $valid = New-RecoveryParseResult -Events @($null, $event) -Diagnostics @($null)
    $invalid = New-RecoveryParseResult -Events @($null) -Diagnostics @($diagnostic, $null)

    Assert-ParserEqual -Actual $valid.Events.Count -Expected 1 -Message 'Valid result event count mismatch.'
    Assert-ParserEqual -Actual $valid.Diagnostics.Count -Expected 0 -Message 'Valid result diagnostic count mismatch.'
    Assert-ParserEqual -Actual $valid.IsValid -Expected $true -Message 'Valid result should be valid.'
    Assert-ParserEqual -Actual $invalid.Events.Count -Expected 0 -Message 'Invalid result event count mismatch.'
    Assert-ParserEqual -Actual $invalid.Diagnostics.Count -Expected 1 -Message 'Invalid result diagnostic count mismatch.'
    Assert-ParserEqual -Actual $invalid.IsValid -Expected $false -Message 'Invalid result should not be valid.'
}

$results | Format-Table -AutoSize | Out-Host

$failed = @($results | Where-Object { -not $_.Passed })
if ($failed.Count -gt 0) {
    throw "Recovery parser verification failed: $($failed.Count)/$($results.Count) failed."
}

Write-Host "Recovery parser verification passed: $($results.Count)/$($results.Count)"

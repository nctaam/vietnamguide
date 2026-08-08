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

Export-ModuleMember -Function New-RecoveryParserDiagnostic, New-RecoveryExecutableEvent, New-RecoveryParseResult

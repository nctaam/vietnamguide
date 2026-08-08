Set-StrictMode -Version 2.0

function New-RecoveryParserDiagnostic {
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

        [Parameter(Mandatory = $true)]
        [string]$FenceId
    )

    [pscustomobject]@{
        Code = $Code
        Message = $Message
        Language = $Language
        SourceLine = $SourceLine
        SourceColumn = $SourceColumn
        FenceId = $FenceId
    }
}

function New-RecoveryExecutableEvent {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Kind,

        [Parameter(Mandatory = $true)]
        [string]$Language,

        [Parameter(Mandatory = $true)]
        [string]$Text,

        [Parameter(Mandatory = $true)]
        [string]$NormalizedCommand,

        [Parameter(Mandatory = $true)]
        [int]$SourceLine,

        [Parameter(Mandatory = $true)]
        [int]$SourceColumn,

        [Parameter(Mandatory = $true)]
        [string]$SectionId,

        [Parameter(Mandatory = $true)]
        [string]$FenceId,

        [Parameter(Mandatory = $true)]
        [string]$StatementId,

        [Parameter(Mandatory = $true)]
        [object]$Metadata
    )

    [pscustomobject]@{
        Kind = $Kind
        Language = $Language
        Text = $Text
        NormalizedCommand = $NormalizedCommand
        SourceLine = $SourceLine
        SourceColumn = $SourceColumn
        SectionId = $SectionId
        FenceId = $FenceId
        StatementId = $StatementId
        Metadata = $Metadata
    }
}

function New-RecoveryParseResult {
    param(
        [object[]]$Events = @(),

        [object[]]$Diagnostics = @()
    )

    $filteredEvents = @($Events | Where-Object { $null -ne $_ })
    $filteredDiagnostics = @($Diagnostics | Where-Object { $null -ne $_ })

    [pscustomobject]@{
        Events = $filteredEvents
        Diagnostics = $filteredDiagnostics
        IsValid = ($filteredDiagnostics.Count -eq 0)
    }
}

Export-ModuleMember -Function New-RecoveryParserDiagnostic, New-RecoveryExecutableEvent, New-RecoveryParseResult

param(
    [string]$BaseUrl = 'https://vietnamguide.net'
)

$ErrorActionPreference = 'Stop'
$Failures = [System.Collections.Generic.List[string]]::new()
$RequestTimeoutSeconds = 20

try {
    $BaseUri = [uri]$BaseUrl
    if ($BaseUri.Scheme -notin @('http', 'https') -or [string]::IsNullOrWhiteSpace($BaseUri.Host)) {
        throw 'BaseUrl must be an absolute HTTP(S) URL.'
    }
    $NormalizedBaseUrl = $BaseUrl.TrimEnd('/')
}
catch {
    Write-Output "FAIL: Invalid BaseUrl '$BaseUrl': $($_.Exception.Message)"
    exit 1
}

function Get-PublicPage {
    param(
        [string]$Path,
        [string]$Label
    )

    $Url = if ($Path -eq '') {
        "$NormalizedBaseUrl/"
    } else {
        "$NormalizedBaseUrl/$($Path.Trim('/'))/"
    }

    try {
        $Response = Invoke-WebRequest `
            -Uri $Url `
            -UseBasicParsing `
            -MaximumRedirection 5 `
            -TimeoutSec $RequestTimeoutSeconds `
            -ErrorAction Stop

        return [pscustomobject]@{
            Label = $Label
            Url = $Url
            StatusCode = [int]$Response.StatusCode
            Content = [string]$Response.Content
        }
    }
    catch {
        $Failures.Add("${Label}: request failed for ${Url}: $($_.Exception.Message)")
        return $null
    }
}

function Require-PublicContains {
    param(
        [pscustomobject]$Page,
        [string]$Needle,
        [string]$Description
    )

    if ($null -ne $Page -and -not $Page.Content.Contains($Needle)) {
        $Failures.Add("$($Page.Label): missing $Description at $($Page.Url)")
    }
}

function Require-PublicNotContains {
    param(
        [pscustomobject]$Page,
        [string]$Needle,
        [string]$Description
    )

    if ($null -ne $Page -and $Page.Content.Contains($Needle)) {
        $Failures.Add("$($Page.Label): found unexpected $Description at $($Page.Url)")
    }
}

function Require-NoFatalText {
    param([pscustomobject]$Page)

    if ($null -eq $Page) {
        return
    }

    foreach ($FatalNeedle in @('fatal error', 'uncaught error', 'there has been a critical error')) {
        if ($Page.Content.IndexOf($FatalNeedle, [System.StringComparison]::OrdinalIgnoreCase) -ge 0) {
            $Failures.Add("$($Page.Label): found fatal text '$FatalNeedle' at $($Page.Url)")
        }
    }
}

$PilotPaths = @(
    'destinations/ho-chi-minh-city-travel-guide'
    'itineraries/10-days-in-vietnam'
    'compare/ha-long-bay-vs-lan-ha-bay'
    'plan/vietnam-evisa'
)

foreach ($PilotPath in $PilotPaths) {
    $Page = Get-PublicPage -Path $PilotPath -Label "pilot $PilotPath"
    if ($null -eq $Page) {
        continue
    }

    if ($Page.StatusCode -ne 200) {
        $Failures.Add("$($Page.Label): expected HTTP 200, found $($Page.StatusCode) at $($Page.Url)")
    }

    $H1Count = [regex]::Matches(
        $Page.Content,
        '<h1\b',
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    ).Count
    if ($H1Count -ne 1) {
        $Failures.Add("$($Page.Label): expected exactly one H1, found $H1Count at $($Page.Url)")
    }

    Require-PublicContains $Page 'data-vg-guide' 'guide shell'
    Require-PublicContains $Page 'guide-experience.css' 'guide experience CSS'
    Require-PublicContains $Page 'guide-experience.js' 'guide experience JavaScript'
    if (-not $Page.Content.Contains('vg-guide-jump') -and -not $Page.Content.Contains('vg-guide-toc')) {
        $Failures.Add("$($Page.Label): missing TOC/jump navigation at $($Page.Url)")
    }
    Require-NoFatalText $Page
}

$Homepage = Get-PublicPage -Path '' -Label 'homepage footer'
if ($null -ne $Homepage) {
    if ($Homepage.StatusCode -ne 200) {
        $Failures.Add("homepage footer: expected HTTP 200, found $($Homepage.StatusCode) at $($Homepage.Url)")
    }

    if (-not [regex]::IsMatch(
        $Homepage.Content,
        'href\s*=\s*["''][^"'']*/source-update-policy/',
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
    )) {
        $Failures.Add("homepage footer: missing /source-update-policy/ link at $($Homepage.Url)")
    }
    Require-NoFatalText $Homepage
}

$NonPilot = Get-PublicPage -Path 'about' -Label 'representative non-pilot page'
if ($null -ne $NonPilot) {
    if ($NonPilot.StatusCode -ne 200) {
        $Failures.Add("representative non-pilot page: expected HTTP 200, found $($NonPilot.StatusCode) at $($NonPilot.Url)")
    }

    Require-PublicNotContains $NonPilot 'data-vg-guide' 'guide shell'
    Require-PublicNotContains $NonPilot 'guide-experience.css' 'guide experience CSS'
    Require-PublicNotContains $NonPilot 'guide-experience.js' 'guide experience JavaScript'
    Require-NoFatalText $NonPilot
}

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output "VietnamGuide public guide experience verification passed for $NormalizedBaseUrl."

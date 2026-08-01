param(
    [string]$BaseUrl = 'https://vietnamguide.net'
)

$ErrorActionPreference = 'Stop'
$Failures = [System.Collections.Generic.List[string]]::new()
$RequestTimeoutSeconds = 20
$ResourceCache = @{}

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

function Resolve-PublicUrl {
    param(
        [string]$PageUrl,
        [string]$Reference,
        [string]$Label
    )

    try {
        $DecodedReference = [System.Net.WebUtility]::HtmlDecode($Reference)
        $Resolved = [uri]::new([uri]$PageUrl, $DecodedReference)
        if ($Resolved.Scheme -notin @('http', 'https') -or [string]::IsNullOrWhiteSpace($Resolved.Host)) {
            throw 'resolved URL was not absolute HTTP(S)'
        }
        return $Resolved.AbsoluteUri
    }
    catch {
        $Failures.Add("${Label}: could not resolve asset URL '$Reference' from ${PageUrl}: $($_.Exception.Message)")
        return $null
    }
}

function Get-PublicResource {
    param(
        [string]$Url,
        [string]$Label
    )

    if ($ResourceCache.ContainsKey($Url)) {
        return $ResourceCache[$Url]
    }

    try {
        $Response = Invoke-WebRequest `
            -Uri $Url `
            -UseBasicParsing `
            -MaximumRedirection 5 `
            -TimeoutSec $RequestTimeoutSeconds `
            -ErrorAction Stop

        $Resource = [pscustomobject]@{
            Url = $Url
            StatusCode = [int]$Response.StatusCode
            Content = [string]$Response.Content
        }
        $ResourceCache[$Url] = $Resource
        return $Resource
    }
    catch {
        $Failures.Add("${Label}: request failed for ${Url}: $($_.Exception.Message)")
        $ResourceCache[$Url] = $null
        return $null
    }
}

function Get-PublicAssetUrl {
    param(
        [pscustomobject]$Page,
        [ValidateSet('css', 'js')]
        [string]$Kind
    )

    if ($null -eq $Page) {
        return $null
    }

    $Pattern = if ($Kind -eq 'css') {
        '(?is)<link\b[^>]*\bhref\s*=\s*["''](?<url>[^"'']*guide-experience\.css(?:\?[^"'']*)?)["''][^>]*>'
    } else {
        '(?is)<script\b[^>]*\bsrc\s*=\s*["''](?<url>[^"'']*guide-experience\.js(?:\?[^"'']*)?)["''][^>]*>'
    }
    $References = @([regex]::Matches($Page.Content, $Pattern) | ForEach-Object { $_.Groups['url'].Value } | Select-Object -Unique)
    $AssetLabel = if ($Kind -eq 'css') { 'guide CSS asset' } else { 'guide JavaScript asset' }
    if ($References.Count -ne 1) {
        $Failures.Add("$($Page.Label): expected exactly one ${AssetLabel} reference, found $($References.Count) at $($Page.Url)")
        return $null
    }

    return Resolve-PublicUrl -PageUrl $Page.Url -Reference $References[0] -Label "$($Page.Label) $AssetLabel"
}

function Get-PublicDomSnapshot {
    param(
        [string]$Html,
        [string]$Label
    )

    $Document = $null
    try {
        # MSHTML treats inert template/noscript contents as live elements and does not nest HTML5 nav elements.
        $RenderableHtml = [regex]::Replace($Html, '(?is)<(?:template|noscript)\b[^>]*>.*?</(?:template|noscript)\s*>', '')
        $RenderableHtml = [regex]::Replace($RenderableHtml, '(?is)<nav\b', '<div data-vg-dom-nav="1"')
        $RenderableHtml = [regex]::Replace($RenderableHtml, '(?is)</nav\s*>', '</div>')
        $Document = New-Object -ComObject HTMLFile
        [void]$Document.IHTMLDocument2_write($RenderableHtml)
        $Document.close()

        $Ids = @(
            $Document.getElementsByTagName('*') |
                ForEach-Object { [string]$_.id } |
                Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
        )
        $Fragments = [System.Collections.Generic.List[string]]::new()
        foreach ($Navigation in $Document.getElementsByTagName('div')) {
            $IsGuideNavigation = [string]$Navigation.getAttribute('data-vg-dom-nav') -eq '1' `
                -and [string]$Navigation.className -match '(^|\s)vg-guide-(?:toc|jump)(\s|$)'
            if (-not $IsGuideNavigation) {
                continue
            }
            foreach ($Element in $Navigation.all) {
                if ([string]$Element.tagName -ne 'A') {
                    continue
                }
                $Href = [string]$Element.getAttribute('href', 2)
                if ($Href.StartsWith('#', [System.StringComparison]::Ordinal)) {
                    $Fragments.Add($Href)
                }
            }
        }

        return [pscustomobject]@{
            Ids = $Ids
            Fragments = $Fragments
        }
    }
    catch {
        $Failures.Add("${Label}: DOM parsing failed: $($_.Exception.Message)")
        return $null
    }
    finally {
        if ($null -ne $Document -and [System.Runtime.InteropServices.Marshal]::IsComObject($Document)) {
            [void][System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($Document)
        }
    }
}

function Require-GuideFragmentTargets {
    param([pscustomobject]$Page)

    if ($null -eq $Page) {
        return
    }

    $Snapshot = Get-PublicDomSnapshot -Html $Page.Content -Label $Page.Label
    if ($null -eq $Snapshot) {
        return
    }

    $IdCounts = [System.Collections.Generic.Dictionary[string, int]]::new([System.StringComparer]::Ordinal)
    foreach ($Id in $Snapshot.Ids) {
        if ($IdCounts.ContainsKey($Id)) {
            $IdCounts[$Id]++
        } else {
            $IdCounts[$Id] = 1
        }
    }

    $FragmentCount = 0
    foreach ($DomHref in $Snapshot.Fragments) {
        $FragmentCount++
        $Href = [System.Net.WebUtility]::HtmlDecode($DomHref)
        try {
            $TargetId = [uri]::UnescapeDataString($Href.Substring(1))
        }
        catch {
            $Failures.Add("$($Page.Label): invalid encoded guide fragment '$Href' at $($Page.Url)")
            continue
        }
        $TargetCount = if ($IdCounts.ContainsKey($TargetId)) { $IdCounts[$TargetId] } else { 0 }
        if ($TargetCount -ne 1) {
            $Failures.Add("$($Page.Label): expected exactly one target ID for fragment '$Href', found $TargetCount at $($Page.Url)")
        }
    }

    if ($FragmentCount -eq 0) {
        $Failures.Add("$($Page.Label): found no TOC/jump fragment links at $($Page.Url)")
    }
}

$DomFixture = Get-PublicDomSnapshot -Label 'DOM parser fixture' -Html '<html><body><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div><a href="#unrelated">Unrelated</a><div id="unrelated"></div><!-- <nav class="vg-guide-toc"><a href="#comment"><span id="comment"></span></a></nav> --><script>var fake = ''<nav class="vg-guide-toc"><a href="#script"><span id="script"></span></a></nav>'';</script><template><nav class="vg-guide-toc"><a href="#template"><span id="template"></span></a></nav></template></body></html>'
if ($null -ne $DomFixture -and (($DomFixture.Ids -join ',') -ne 'real,unrelated' -or ($DomFixture.Fragments -join ',') -ne '#real')) {
    $Failures.Add('DOM parser fixture counted inert or unrelated markup as guide navigation')
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
    foreach ($AssetSpec in @(
        @{ Kind = 'css'; Label = 'guide CSS asset' }
        @{ Kind = 'js'; Label = 'guide JavaScript asset' }
    )) {
        $AssetUrl = Get-PublicAssetUrl -Page $Page -Kind $AssetSpec.Kind
        if ($null -eq $AssetUrl) {
            continue
        }
        $Asset = Get-PublicResource -Url $AssetUrl -Label "$($Page.Label) $($AssetSpec.Label)"
        if ($null -ne $Asset -and $Asset.StatusCode -ne 200) {
            $Failures.Add("$($Page.Label): $($AssetSpec.Label) expected HTTP 200, found $($Asset.StatusCode) at $($Asset.Url)")
        }
    }
    Require-GuideFragmentTargets $Page
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

$NonPilotPaths = @(
    'destinations/hanoi-travel-guide'
    'itineraries/14-days-in-vietnam'
    'compare/da-nang-vs-hoi-an'
    'plan/sim-esim-vietnam'
)

foreach ($NonPilotPath in $NonPilotPaths) {
    $NonPilot = Get-PublicPage -Path $NonPilotPath -Label "non-pilot $NonPilotPath"
    if ($null -eq $NonPilot) {
        continue
    }
    if ($NonPilot.StatusCode -ne 200) {
        $Failures.Add("$($NonPilot.Label): expected HTTP 200, found $($NonPilot.StatusCode) at $($NonPilot.Url)")
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

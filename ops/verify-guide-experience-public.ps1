param(
    [string]$BaseUrl = 'https://vietnamguide.net',
    [switch]$FixturesOnly
)

$ErrorActionPreference = 'Stop'
$Failures = [System.Collections.Generic.List[string]]::new()
$RequestTimeoutSeconds = 20
$ResourceCache = @{}

function Get-NormalizedOriginKey {
    param([uri]$Uri)

    if ($Uri.Scheme -notin @('http', 'https') -or [string]::IsNullOrWhiteSpace($Uri.Host)) {
        throw 'URI must be absolute HTTP(S).'
    }
    if (-not [string]::IsNullOrEmpty($Uri.UserInfo)) {
        throw 'URI credentials are forbidden.'
    }

    $EffectivePort = if ($Uri.IsDefaultPort) {
        if ($Uri.Scheme -eq 'https') { 443 } else { 80 }
    } else {
        $Uri.Port
    }
    return '{0}://{1}:{2}' -f $Uri.Scheme.ToLowerInvariant(), $Uri.DnsSafeHost.ToLowerInvariant(), $EffectivePort
}

function Get-PublicExpectedAssets {
    param([string]$AssetsRoot)

    $Specs = [ordered]@{
        css = [ordered]@{
            Path = '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css'
            LocalPath = Join-Path $AssetsRoot 'css\guide-experience.css'
            AllowedContentTypes = @('text/css')
        }
        js = [ordered]@{
            Path = '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
            LocalPath = Join-Path $AssetsRoot 'js\guide-experience.js'
            AllowedContentTypes = @('application/ecmascript', 'application/javascript', 'application/x-javascript', 'text/ecmascript', 'text/javascript')
        }
    }

    foreach ($Kind in @('css', 'js')) {
        $LocalPath = [string]$Specs[$Kind].LocalPath
        if (-not (Test-Path -LiteralPath $LocalPath -PathType Leaf)) {
            throw "${Kind} local reviewed asset is missing: $LocalPath"
        }
        $Specs[$Kind].Sha256 = (Get-FileHash -LiteralPath $LocalPath -Algorithm SHA256).Hash.ToLowerInvariant()
        $Specs[$Kind].Version = $Specs[$Kind].Sha256
    }
    return $Specs
}

try {
    $BaseUri = [uri]$BaseUrl
    $BaseOriginKey = Get-NormalizedOriginKey $BaseUri
    $NormalizedBaseUrl = $BaseUri.GetLeftPart([System.UriPartial]::Path).TrimEnd('/')
}
catch {
    Write-Output "FAIL: Invalid BaseUrl '$BaseUrl': $($_.Exception.Message)"
    exit 1
}

try {
    $LocalAssetsRoot = Join-Path $PSScriptRoot '..\wordpress\wp-content\themes\vietnamguide-premium\assets'
    $ExpectedAssets = Get-PublicExpectedAssets -AssetsRoot $LocalAssetsRoot
}
catch {
    Write-Output "FAIL: $($_.Exception.Message)"
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

        $Content = [string]$Response.Content
        $Dom = Get-PublicDomSnapshot -Html $Content -Label $Label

        return [pscustomobject]@{
            Label = $Label
            Url = $Url
            StatusCode = [int]$Response.StatusCode
            Content = $Content
            Dom = $Dom
        }
    }
    catch {
        $Failures.Add("${Label}: request failed for ${Url}: $($_.Exception.Message)")
        return $null
    }
}

function Test-PublicAssetUri {
    param(
        [string]$PageUrl,
        [string]$Reference,
        [System.Collections.IDictionary]$ExpectedAsset
    )

    try {
        $DecodedReference = [System.Net.WebUtility]::HtmlDecode($Reference)
        $Resolved = [uri]::new([uri]$PageUrl, $DecodedReference)
        if (-not [string]::IsNullOrEmpty($Resolved.UserInfo)) {
            throw 'asset URL credentials are forbidden'
        }
        if ((Get-NormalizedOriginKey $Resolved) -ne $BaseOriginKey) {
            throw 'asset URL origin did not match BaseUrl exactly'
        }
        if ($Resolved.AbsolutePath -cne $ExpectedAsset.Path) {
            throw "asset URL path must be $($ExpectedAsset.Path)"
        }
        $ExpectedQuery = '?ver=' + [string]$ExpectedAsset.Version
        if ($Resolved.Query -cne $ExpectedQuery) {
            throw "asset URL query must exactly match content version $($ExpectedAsset.Version)"
        }
        if ($Resolved.Fragment -ne '') {
            throw 'asset URL fragments are forbidden'
        }
        return [pscustomobject]@{ Uri = $Resolved; Error = $null }
    }
    catch {
        return [pscustomobject]@{ Uri = $null; Error = $_.Exception.Message }
    }
}

function Get-ByteSha256 {
    param([byte[]]$Bytes)

    $Sha256 = [System.Security.Cryptography.SHA256]::Create()
    try {
        return ([System.BitConverter]::ToString($Sha256.ComputeHash($Bytes))).Replace('-', '').ToLowerInvariant()
    }
    finally {
        $Sha256.Dispose()
    }
}

function Test-PublicAssetResponse {
    param(
        [int]$StatusCode,
        [string]$ContentTypeHeader,
        [byte[]]$Bytes,
        [uri]$ResponseUri,
        [uri]$RequestedUri,
        [System.Collections.IDictionary]$ExpectedAsset
    )

    $Errors = [System.Collections.Generic.List[string]]::new()
    if ($StatusCode -ne 200) {
        $Errors.Add("expected HTTP 200, found $StatusCode")
    }
    if ($null -eq $ResponseUri -or $null -eq $RequestedUri) {
        $Errors.Add('response URI evidence was missing')
    } elseif ($ResponseUri.AbsoluteUri -ne $RequestedUri.AbsoluteUri) {
        $Errors.Add("response URI changed to $($ResponseUri.AbsoluteUri)")
    }

    $ContentType = (($ContentTypeHeader -split ';', 2)[0]).Trim().ToLowerInvariant()
    $AllowedContentTypes = @($ExpectedAsset.AllowedContentTypes | ForEach-Object { $_.ToLowerInvariant() })
    if ($AllowedContentTypes -notcontains $ContentType) {
        $Errors.Add("unexpected Content-Type '$ContentTypeHeader'")
    }

    $ActualHash = Get-ByteSha256 -Bytes $Bytes
    if (-not $ActualHash.Equals($ExpectedAsset.Sha256, [System.StringComparison]::OrdinalIgnoreCase)) {
        $Errors.Add("SHA-256 mismatch: expected $($ExpectedAsset.Sha256), found $ActualHash")
    }
    return $Errors.ToArray()
}

function Get-PublicResource {
    param(
        [uri]$Url,
        [string]$Label,
        [System.Collections.IDictionary]$ExpectedAsset
    )

    $CacheKey = $Url.AbsoluteUri
    if ($ResourceCache.ContainsKey($CacheKey)) {
        return $ResourceCache[$CacheKey]
    }

    try {
        $Response = Invoke-WebRequest `
            -Uri $Url.AbsoluteUri `
            -UseBasicParsing `
            -MaximumRedirection 0 `
            -TimeoutSec $RequestTimeoutSeconds `
            -ErrorAction Stop

        if ($null -eq $Response.RawContentStream) {
            throw 'response byte stream was unavailable'
        }
        if ($Response.RawContentStream.CanSeek) {
            $Response.RawContentStream.Position = 0
        }
        $Memory = New-Object System.IO.MemoryStream
        try {
            $Response.RawContentStream.CopyTo($Memory)
            $Bytes = $Memory.ToArray()
        }
        finally {
            $Memory.Dispose()
        }

        $ResponseUri = if ($null -ne $Response.BaseResponse) { [uri]$Response.BaseResponse.ResponseUri } else { $null }
        $ResponseFailures = @(Test-PublicAssetResponse `
            -StatusCode ([int]$Response.StatusCode) `
            -ContentTypeHeader ([string]$Response.Headers['Content-Type']) `
            -Bytes $Bytes `
            -ResponseUri $ResponseUri `
            -RequestedUri $Url `
            -ExpectedAsset $ExpectedAsset)
        if ($ResponseFailures.Count -gt 0) {
            foreach ($ResponseFailure in $ResponseFailures) {
                $Failures.Add("${Label}: $ResponseFailure at $($Url.AbsoluteUri)")
            }
            $ResourceCache[$CacheKey] = $null
            return $null
        }

        $Resource = [pscustomobject]@{
            Url = $Url.AbsoluteUri
            StatusCode = [int]$Response.StatusCode
            ContentType = [string]$Response.Headers['Content-Type']
            Sha256 = Get-ByteSha256 -Bytes $Bytes
        }
        $ResourceCache[$CacheKey] = $Resource
        return $Resource
    }
    catch {
        $RedirectStatus = $null
        if ($null -ne $_.Exception.Response) {
            try { $RedirectStatus = [int]$_.Exception.Response.StatusCode } catch { $RedirectStatus = $null }
        }
        if ($null -ne $RedirectStatus -and $RedirectStatus -ge 300 -and $RedirectStatus -lt 400) {
            $Failures.Add("${Label}: redirect response rejected (HTTP $RedirectStatus) at $($Url.AbsoluteUri)")
        } else {
            $Failures.Add("${Label}: request failed for $($Url.AbsoluteUri): $($_.Exception.Message)")
        }
        $ResourceCache[$CacheKey] = $null
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

    if ($null -eq $Page.Dom) {
        return $null
    }
    $References = @($Page.Dom.AssetReferences[$Kind])
    $AssetLabel = if ($Kind -eq 'css') { 'guide CSS asset' } else { 'guide JavaScript asset' }
    if ($References.Count -ne 1) {
        $Failures.Add("$($Page.Label): expected exactly one ${AssetLabel} reference, found $($References.Count) at $($Page.Url)")
        return $null
    }

    $Validation = Test-PublicAssetUri -PageUrl $Page.Url -Reference $References[0] -ExpectedAsset $ExpectedAssets[$Kind]
    if ($null -ne $Validation.Error) {
        $Failures.Add("$($Page.Label): invalid ${AssetLabel} URL '$($References[0])': $($Validation.Error) at $($Page.Url)")
        return $null
    }
    return $Validation.Uri
}

function Find-PublicHtmlTagEnd {
    param(
        [string]$Html,
        [int]$StartIndex
    )

    $Quote = [char]0
    for ($Index = $StartIndex + 1; $Index -lt $Html.Length; $Index++) {
        $Character = $Html[$Index]
        if ($Quote -ne [char]0) {
            if ($Character -eq $Quote) {
                $Quote = [char]0
            }
            continue
        }
        if ($Character -eq '"' -or $Character -eq "'") {
            $Quote = $Character
            continue
        }
        if ($Character -eq '>') {
            return $Index
        }
    }
    return -1
}

function Test-PublicHtmlTagNameDelimiter {
    param(
        [string]$Html,
        [int]$Index,
        [bool]$IsClosing
    )

    if ($Index -lt 0 -or $Index -ge $Html.Length) {
        return $false
    }
    $Character = $Html[$Index]
    if ($Character -eq '>' -or $Character -match '[\x09\x0A\x0C\x0D ]') {
        return $true
    }
    return -not $IsClosing -and $Character -eq '/'
}

function Test-PublicHtmlSequenceAt {
    param(
        [string]$Html,
        [int]$Index,
        [string]$Needle
    )

    if ($Index -lt 0 -or $Index + $Needle.Length -gt $Html.Length) {
        return $false
    }
    return $Html.IndexOf($Needle, $Index, $Needle.Length, [System.StringComparison]::OrdinalIgnoreCase) -eq $Index
}

# Bounded script-tokenizer states needed to keep inert DOM filtering conservative.
function Find-PublicScriptEnd {
    param(
        [string]$Html,
        [int]$ContentStart
    )

    $State = 'data'
    $Index = $ContentStart
    $OpenNeedle = '<script'
    $CloseNeedle = '</script'
    while ($Index -lt $Html.Length) {
        if ($State -eq 'data') {
            if (Test-PublicHtmlSequenceAt -Html $Html -Index $Index -Needle '<!--') {
                $State = 'escaped'
                $Index += 4
                continue
            }
        } elseif ($State -eq 'escaped') {
            if (Test-PublicHtmlSequenceAt -Html $Html -Index $Index -Needle '-->') {
                $State = 'data'
                $Index += 3
                continue
            }
            if (Test-PublicHtmlSequenceAt -Html $Html -Index $Index -Needle $OpenNeedle) {
                $OpenNameEnd = $Index + $OpenNeedle.Length
                if (Test-PublicHtmlTagNameDelimiter -Html $Html -Index $OpenNameEnd -IsClosing $false) {
                    $State = 'double-escaped'
                    $Index = $OpenNameEnd
                    continue
                }
            }
        } else {
            if (Test-PublicHtmlSequenceAt -Html $Html -Index $Index -Needle '-->') {
                $State = 'data'
                $Index += 3
                continue
            }
            if (Test-PublicHtmlSequenceAt -Html $Html -Index $Index -Needle $CloseNeedle) {
                $CloseNameEnd = $Index + $CloseNeedle.Length
                if (Test-PublicHtmlTagNameDelimiter -Html $Html -Index $CloseNameEnd -IsClosing $true) {
                    $State = 'escaped'
                    $Index = $CloseNameEnd
                    continue
                }
            }
        }

        if ($State -ne 'double-escaped' -and (Test-PublicHtmlSequenceAt -Html $Html -Index $Index -Needle $CloseNeedle)) {
            $CloseNameEnd = $Index + $CloseNeedle.Length
            if (Test-PublicHtmlTagNameDelimiter -Html $Html -Index $CloseNameEnd -IsClosing $true) {
                $CloseEnd = Find-PublicHtmlTagEnd -Html $Html -StartIndex $Index
                if ($CloseEnd -lt 0) {
                    return $null
                }
                return [pscustomobject]@{ Start = $Index; End = $CloseEnd }
            }
        }
        $Index++
    }
    return $null
}

function Find-PublicRawTextEnd {
    param(
        [string]$Html,
        [int]$ContentStart,
        [string]$TagName
    )

    if ($TagName -eq 'script') {
        return Find-PublicScriptEnd -Html $Html -ContentStart $ContentStart
    }
    if ($TagName -eq 'plaintext') {
        return $null
    }
    $Needle = '</' + $TagName
    $SearchIndex = $ContentStart
    while ($SearchIndex -lt $Html.Length) {
        $CloseStart = $Html.IndexOf($Needle, $SearchIndex, [System.StringComparison]::OrdinalIgnoreCase)
        if ($CloseStart -lt 0) {
            return $null
        }
        $NameEnd = $CloseStart + $Needle.Length
        if (-not (Test-PublicHtmlTagNameDelimiter -Html $Html -Index $NameEnd -IsClosing $true)) {
            $SearchIndex = $NameEnd
            continue
        }
        $CloseEnd = Find-PublicHtmlTagEnd -Html $Html -StartIndex $CloseStart
        if ($CloseEnd -lt 0) {
            return $null
        }
        return [pscustomobject]@{ Start = $CloseStart; End = $CloseEnd }
    }
    return $null
}

function Convert-PublicHtmlForMshtml {
    param(
        [string]$Html,
        [string]$NavMarker
    )

    $Output = New-Object System.Text.StringBuilder
    $InertStack = [System.Collections.Generic.List[string]]::new()
    $RawTextTags = @('iframe', 'noembed', 'noframes', 'plaintext', 'script', 'style', 'textarea', 'title', 'xmp')
    $Index = 0

    while ($Index -lt $Html.Length) {
        if ($Html[$Index] -ne '<') {
            $NextTag = $Html.IndexOf('<', $Index)
            if ($NextTag -lt 0) { $NextTag = $Html.Length }
            if ($InertStack.Count -eq 0) {
                [void]$Output.Append($Html.Substring($Index, $NextTag - $Index))
            }
            $Index = $NextTag
            continue
        }

        if ($Html.IndexOf('<!--', $Index, [System.StringComparison]::Ordinal) -eq $Index) {
            $CommentEnd = $Html.IndexOf('-->', $Index + 4, [System.StringComparison]::Ordinal)
            $TokenEnd = if ($CommentEnd -lt 0) { $Html.Length - 1 } else { $CommentEnd + 2 }
            if ($InertStack.Count -eq 0) {
                [void]$Output.Append($Html.Substring($Index, $TokenEnd - $Index + 1))
            }
            $Index = $TokenEnd + 1
            continue
        }

        $TagEnd = Find-PublicHtmlTagEnd -Html $Html -StartIndex $Index
        if ($TagEnd -lt 0) {
            if ($InertStack.Count -eq 0) {
                [void]$Output.Append($Html.Substring($Index))
            }
            break
        }
        $Token = $Html.Substring($Index, $TagEnd - $Index + 1)
        $TagMatch = [regex]::Match($Token, '^<(?<closing>/)?(?<name>[A-Za-z][A-Za-z0-9:-]*)', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        if (-not $TagMatch.Success) {
            if ($InertStack.Count -eq 0) {
                [void]$Output.Append($Token)
            }
            $Index = $TagEnd + 1
            continue
        }

        $TagName = $TagMatch.Groups['name'].Value.ToLowerInvariant()
        $IsClosing = $TagMatch.Groups['closing'].Value -eq '/'
        $NameEnd = $TagMatch.Groups['name'].Index + $TagMatch.Groups['name'].Length
        if (-not (Test-PublicHtmlTagNameDelimiter -Html $Token -Index $NameEnd -IsClosing $IsClosing)) {
            if ($InertStack.Count -eq 0) {
                [void]$Output.Append($Token)
            }
            $Index = $TagEnd + 1
            continue
        }
        if (-not $IsClosing -and $RawTextTags -contains $TagName) {
            $RawEnd = Find-PublicRawTextEnd -Html $Html -ContentStart ($TagEnd + 1) -TagName $TagName
            $BlockEnd = if ($null -eq $RawEnd) { $Html.Length - 1 } else { $RawEnd.End }
            if ($InertStack.Count -eq 0) {
                [void]$Output.Append($Html.Substring($Index, $BlockEnd - $Index + 1))
            }
            $Index = $BlockEnd + 1
            continue
        }

        if ($TagName -in @('template', 'noscript')) {
            if ($IsClosing) {
                if ($InertStack.Count -gt 0) {
                    $TopIndex = $InertStack.Count - 1
                    if ($InertStack[$TopIndex] -ne $TagName) {
                        throw "mismatched inert HTML closing tag: $TagName"
                    }
                    $InertStack.RemoveAt($TopIndex)
                }
            } else {
                $InertStack.Add($TagName)
            }
            $Index = $TagEnd + 1
            continue
        }

        if ($InertStack.Count -eq 0) {
            if ($TagName -eq 'nav') {
                if ($IsClosing) {
                    [void]$Output.Append('</div>')
                } else {
                    $NameGroup = $TagMatch.Groups['name']
                    $Rewritten = $Token.Substring(0, $NameGroup.Index) `
                        + 'div data-vg-dom-nav="' + $NavMarker + '"' `
                        + $Token.Substring($NameGroup.Index + $NameGroup.Length)
                    [void]$Output.Append($Rewritten)
                }
            } else {
                [void]$Output.Append($Token)
            }
        }
        $Index = $TagEnd + 1
    }

    if ($InertStack.Count -ne 0) {
        throw 'incomplete inert HTML subtree'
    }
    return $Output.ToString()
}

function Get-PublicDomSnapshot {
    param(
        [string]$Html,
        [string]$Label
    )

    $Document = $null
    try {
        $NavMarker = [guid]::NewGuid().ToString('N')
        $RenderableHtml = Convert-PublicHtmlForMshtml -Html $Html -NavMarker $NavMarker
        $Document = New-Object -ComObject HTMLFile
        [void]$Document.IHTMLDocument2_write($RenderableHtml)
        $Document.close()

        $AllElements = @($Document.getElementsByTagName('*'))
        $Ids = @($AllElements | ForEach-Object { [string]$_.id } | Where-Object { -not [string]::IsNullOrWhiteSpace($_) })
        $HasGuideShell = $false
        foreach ($Element in $AllElements) {
            if ($null -ne $Element.getAttributeNode('data-vg-guide')) {
                $HasGuideShell = $true
                break
            }
        }

        $HasGuideNavigation = $false
        $Fragments = [System.Collections.Generic.List[string]]::new()
        foreach ($Navigation in $Document.getElementsByTagName('div')) {
            $IsGuideNavigation = [string]$Navigation.getAttribute('data-vg-dom-nav') -eq $NavMarker `
                -and [string]$Navigation.className -match '(^|\s)vg-guide-(?:toc|jump)(\s|$)'
            if (-not $IsGuideNavigation) {
                continue
            }
            $HasGuideNavigation = $true
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

        $CssReferences = [System.Collections.Generic.List[string]]::new()
        foreach ($Link in $Document.getElementsByTagName('link')) {
            $RelTokens = @(([string]$Link.getAttribute('rel')).ToLowerInvariant() -split '\s+' | Where-Object { $_ -ne '' })
            $IsStylesheet = $RelTokens -contains 'stylesheet'
            $Href = [string]$Link.getAttribute('href', 2)
            if ($IsStylesheet -and $Href -match '(?i)guide-experience\.css(?:[?#]|$)') {
                $CssReferences.Add($Href)
            }
        }

        $JsReferences = [System.Collections.Generic.List[string]]::new()
        foreach ($Script in $Document.getElementsByTagName('script')) {
            $Src = [string]$Script.getAttribute('src', 2)
            if ($Src -match '(?i)guide-experience\.js(?:[?#]|$)') {
                $JsReferences.Add($Src)
            }
        }

        return [pscustomobject]@{
            Ids = $Ids
            Fragments = $Fragments
            H1Count = @($Document.getElementsByTagName('h1')).Count
            HasGuideShell = $HasGuideShell
            HasGuideNavigation = $HasGuideNavigation
            AssetReferences = @{
                css = $CssReferences.ToArray()
                js = $JsReferences.ToArray()
            }
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

    $Snapshot = $Page.Dom
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

$DomFixtureHtml = '<html><head><link rel="preload" href="/wrong/guide-experience.css"><link rel="stylesheet" href="/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture"><script src="/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture"></script></head><body><article data-vg-guide><h1>Real title</h1><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div></article><a href="#unrelated">Unrelated</a><div id="unrelated"></div><!-- <h1>Comment title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav><link rel="stylesheet" href="/fake/guide-experience.css"><script src="/fake/guide-experience.js"></script></article> --><script>var fake = ''<h1>Script title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav><link rel="stylesheet" href="/script/guide-experience.css"><script src="/script/guide-experience.js"></script></article>'';</script><template><h1>Template title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav><link rel="stylesheet" href="/template/guide-experience.css"><script src="/template/guide-experience.js"></script></article></template><noscript><h1>Noscript title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav></article></noscript></body></html>'
$DomFixture = Get-PublicDomSnapshot -Label 'DOM parser fixture' -Html $DomFixtureHtml
if ($null -ne $DomFixture -and (
    $DomFixture.H1Count -ne 1 `
        -or -not $DomFixture.HasGuideShell `
        -or -not $DomFixture.HasGuideNavigation `
        -or ($DomFixture.Ids -join ',') -ne 'real,unrelated' `
        -or ($DomFixture.Fragments -join ',') -ne '#real' `
        -or ($DomFixture.AssetReferences.css -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture' `
        -or ($DomFixture.AssetReferences.js -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture'
)) {
    $Failures.Add('DOM parser fixture accepted inert pseudo guide markup')
}
$InertDomFixture = Get-PublicDomSnapshot -Label 'inert DOM parser fixture' -Html '<html><body><!-- <h1>Comment title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav><link rel="stylesheet" href="/comment/guide-experience.css"><script src="/comment/guide-experience.js"></script></article> --><script>var fake = ''<h1>Script title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav><link rel="stylesheet" href="/script/guide-experience.css"><script src="/script/guide-experience.js"></script></article>'';</script><template><h1>Template title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav><link rel="stylesheet" href="/template/guide-experience.css"><script src="/template/guide-experience.js"></script></article></template><noscript><h1>Noscript title</h1><article data-vg-guide><nav class="vg-guide-toc"></nav></article></noscript></body></html>'
if ($null -ne $InertDomFixture -and (
    $InertDomFixture.H1Count -ne 0 `
        -or $InertDomFixture.HasGuideShell `
        -or $InertDomFixture.HasGuideNavigation `
        -or @($InertDomFixture.AssetReferences.css).Count -ne 0 `
        -or @($InertDomFixture.AssetReferences.js).Count -ne 0
)) {
    $Failures.Add('DOM parser fixture accepted inert pseudo guide markup')
}
$RawTextInertFixture = Get-PublicDomSnapshot -Label 'raw-text inert DOM parser fixture' -Html '<html><body><template><script>var marker = "</template>";</script><h1>Inert title</h1><article data-vg-guide><nav class="vg-guide-toc"><a href="#inert">Inert</a></nav><div id="inert"></div></article></template></body></html>'
if ($null -ne $RawTextInertFixture -and (
    $RawTextInertFixture.H1Count -ne 0 `
        -or $RawTextInertFixture.HasGuideShell `
        -or $RawTextInertFixture.HasGuideNavigation `
        -or @($RawTextInertFixture.Fragments).Count -ne 0 `
        -or @($RawTextInertFixture.Ids).Count -ne 0
)) {
    $Failures.Add('inert raw-text fixture exposed template descendants')
}
$ScriptDoubleEscapedFixture = Get-PublicDomSnapshot -Label 'script double-escaped inert fixture' -Html '<html><body><template><script><!--<script></script></template><h1>Fake title</h1><article data-vg-guide><nav class="vg-guide-toc"><a href="#fake">Fake</a></nav><div id="fake"></div><link rel="stylesheet" href="/fake/guide-experience.css?ver=fake"><script src="/fake/guide-experience.js?ver=fake"></script></article></script></template></body></html>'
if ($null -ne $ScriptDoubleEscapedFixture -and (
    $ScriptDoubleEscapedFixture.H1Count -ne 0 `
        -or $ScriptDoubleEscapedFixture.HasGuideShell `
        -or $ScriptDoubleEscapedFixture.HasGuideNavigation `
        -or @($ScriptDoubleEscapedFixture.AssetReferences.css).Count -ne 0 `
        -or @($ScriptDoubleEscapedFixture.AssetReferences.js).Count -ne 0 `
        -or @($ScriptDoubleEscapedFixture.Fragments).Count -ne 0 `
        -or @($ScriptDoubleEscapedFixture.Ids).Count -ne 0
)) {
    $Failures.Add('script double-escaped fixture exposed inert guide markup')
}
$ScriptOrdinaryCloseFixture = Get-PublicDomSnapshot -Label 'ordinary script close fixture' -Html '<html><body><template><script>var marker = 1;</script></template><article data-vg-guide><h1>Real title</h1><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div><link rel="stylesheet" href="/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture"><script src="/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture"></script></article></body></html>'
if ($null -ne $ScriptOrdinaryCloseFixture -and (
    $ScriptOrdinaryCloseFixture.H1Count -ne 1 `
        -or -not $ScriptOrdinaryCloseFixture.HasGuideShell `
        -or -not $ScriptOrdinaryCloseFixture.HasGuideNavigation `
        -or ($ScriptOrdinaryCloseFixture.AssetReferences.css -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture' `
        -or ($ScriptOrdinaryCloseFixture.AssetReferences.js -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture' `
        -or ($ScriptOrdinaryCloseFixture.Fragments -join ',') -ne '#real' `
        -or ($ScriptOrdinaryCloseFixture.Ids -join ',') -ne 'real'
)) {
    $Failures.Add('ordinary script close fixture did not expose reviewed guide markup')
}
$ScriptEscapedCloseFixture = Get-PublicDomSnapshot -Label 'escaped script close fixture' -Html '<html><body><template><script><!-- escaped marker </script></template><article data-vg-guide><h1>Real title</h1><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div><link rel="stylesheet" href="/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture"><script src="/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture"></script></article></body></html>'
if ($null -ne $ScriptEscapedCloseFixture -and (
    $ScriptEscapedCloseFixture.H1Count -ne 1 `
        -or -not $ScriptEscapedCloseFixture.HasGuideShell `
        -or -not $ScriptEscapedCloseFixture.HasGuideNavigation `
        -or ($ScriptEscapedCloseFixture.AssetReferences.css -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture' `
        -or ($ScriptEscapedCloseFixture.AssetReferences.js -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture' `
        -or ($ScriptEscapedCloseFixture.Fragments -join ',') -ne '#real' `
        -or ($ScriptEscapedCloseFixture.Ids -join ',') -ne 'real'
)) {
    $Failures.Add('escaped script close fixture did not expose reviewed guide markup')
}
$ScriptDoubleEscapedCommentCloseFixture = Get-PublicDomSnapshot -Label 'double-escaped script comment close fixture' -Html '<html><body><template><script><!--<script>--><script></script></template><article data-vg-guide><h1>Real title</h1><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div><link rel="stylesheet" href="/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture"><script src="/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture"></script></article></script></template></body></html>'
if ($null -ne $ScriptDoubleEscapedCommentCloseFixture -and (
    $ScriptDoubleEscapedCommentCloseFixture.H1Count -ne 1 `
        -or -not $ScriptDoubleEscapedCommentCloseFixture.HasGuideShell `
        -or -not $ScriptDoubleEscapedCommentCloseFixture.HasGuideNavigation `
        -or ($ScriptDoubleEscapedCommentCloseFixture.AssetReferences.css -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture' `
        -or ($ScriptDoubleEscapedCommentCloseFixture.AssetReferences.js -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture' `
        -or ($ScriptDoubleEscapedCommentCloseFixture.Fragments -join ',') -ne '#real' `
        -or ($ScriptDoubleEscapedCommentCloseFixture.Ids -join ',') -ne 'real'
)) {
    $Failures.Add('double-escaped script comment close did not return to script data')
}
$MalformedTemplateDelimiterFixture = Get-PublicDomSnapshot -Label 'malformed template closing delimiter fixture' -Html '<html><body><template></template!><h1>Fake title</h1><article data-vg-guide><nav class="vg-guide-toc"><a href="#fake">Fake</a></nav><div id="fake"></div><link rel="stylesheet" href="/fake/guide-experience.css?ver=fake"><script src="/fake/guide-experience.js?ver=fake"></script></article></template></body></html>'
if ($null -ne $MalformedTemplateDelimiterFixture -and (
    $MalformedTemplateDelimiterFixture.H1Count -ne 0 `
        -or $MalformedTemplateDelimiterFixture.HasGuideShell `
        -or $MalformedTemplateDelimiterFixture.HasGuideNavigation `
        -or @($MalformedTemplateDelimiterFixture.AssetReferences.css).Count -ne 0 `
        -or @($MalformedTemplateDelimiterFixture.AssetReferences.js).Count -ne 0 `
        -or @($MalformedTemplateDelimiterFixture.Fragments).Count -ne 0 `
        -or @($MalformedTemplateDelimiterFixture.Ids).Count -ne 0
)) {
    $Failures.Add('malformed template closing delimiter exposed inert descendants')
}
$MalformedRawTextDelimiterFixture = Get-PublicDomSnapshot -Label 'malformed raw-text closing delimiter fixture' -Html '<html><body><template><script>var marker = "</script!></template>";</script><h1>Fake title</h1><article data-vg-guide><nav class="vg-guide-toc"><a href="#fake">Fake</a></nav><div id="fake"></div><link rel="stylesheet" href="/fake/guide-experience.css?ver=fake"><script src="/fake/guide-experience.js?ver=fake"></script></article></template></body></html>'
if ($null -ne $MalformedRawTextDelimiterFixture -and (
    $MalformedRawTextDelimiterFixture.H1Count -ne 0 `
        -or $MalformedRawTextDelimiterFixture.HasGuideShell `
        -or $MalformedRawTextDelimiterFixture.HasGuideNavigation `
        -or @($MalformedRawTextDelimiterFixture.AssetReferences.css).Count -ne 0 `
        -or @($MalformedRawTextDelimiterFixture.AssetReferences.js).Count -ne 0 `
        -or @($MalformedRawTextDelimiterFixture.Fragments).Count -ne 0 `
        -or @($MalformedRawTextDelimiterFixture.Ids).Count -ne 0
)) {
    $Failures.Add('malformed raw-text closing delimiter exposed inert descendants')
}
$ValidClosingDelimiterFixture = Get-PublicDomSnapshot -Label 'valid closing delimiter fixture' -Html '<html><body><template><h1>Inert template title</h1></template   ><script>var fake = ''<h1>Inert script title</h1>'';</script   ><article data-vg-guide><h1>Real title</h1><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div><link rel="stylesheet" href="/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture"><script src="/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture"></script></article></body></html>'
if ($null -ne $ValidClosingDelimiterFixture -and (
    $ValidClosingDelimiterFixture.H1Count -ne 1 `
        -or -not $ValidClosingDelimiterFixture.HasGuideShell `
        -or -not $ValidClosingDelimiterFixture.HasGuideNavigation `
        -or ($ValidClosingDelimiterFixture.AssetReferences.css -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture' `
        -or ($ValidClosingDelimiterFixture.AssetReferences.js -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture' `
        -or ($ValidClosingDelimiterFixture.Fragments -join ',') -ne '#real' `
        -or ($ValidClosingDelimiterFixture.Ids -join ',') -ne 'real'
)) {
    $Failures.Add('valid whitespace closing delimiter was rejected')
}
$MalformedTemplatePrefixFixtures = @(
    @{ Label = 'space before closing slash'; Token = '< /template>' }
    @{ Label = 'space after closing slash'; Token = '</ template>' }
)
foreach ($FixtureSpec in $MalformedTemplatePrefixFixtures) {
    $FixtureHtml = '<html><body><template>{{TOKEN}}<h1>Fake title</h1><article data-vg-guide><nav class="vg-guide-toc"><a href="#fake">Fake</a></nav><div id="fake"></div><link rel="stylesheet" href="/fake/guide-experience.css?ver=fake"><script src="/fake/guide-experience.js?ver=fake"></script></article></template></body></html>'.Replace('{{TOKEN}}', $FixtureSpec.Token)
    $Fixture = Get-PublicDomSnapshot -Label "malformed template prefix fixture ($($FixtureSpec.Label))" -Html $FixtureHtml
    if ($null -ne $Fixture -and (
        $Fixture.H1Count -ne 0 `
            -or $Fixture.HasGuideShell `
            -or $Fixture.HasGuideNavigation `
            -or @($Fixture.AssetReferences.css).Count -ne 0 `
            -or @($Fixture.AssetReferences.js).Count -ne 0 `
            -or @($Fixture.Fragments).Count -ne 0 `
            -or @($Fixture.Ids).Count -ne 0
    )) {
        $Failures.Add("malformed template tag prefix exposed inert descendants: $($FixtureSpec.Label)")
    }
}
$MalformedRawTextPrefixFixtures = @(
    @{ Label = 'space before closing slash'; Token = '< /script>' }
    @{ Label = 'space after closing slash'; Token = '</ script>' }
)
foreach ($FixtureSpec in $MalformedRawTextPrefixFixtures) {
    $FixtureHtml = '<html><body><template><script>var marker = "{{TOKEN}}</template>";</script><h1>Fake title</h1><article data-vg-guide><nav class="vg-guide-toc"><a href="#fake">Fake</a></nav><div id="fake"></div><link rel="stylesheet" href="/fake/guide-experience.css?ver=fake"><script src="/fake/guide-experience.js?ver=fake"></script></article></template></body></html>'.Replace('{{TOKEN}}', $FixtureSpec.Token)
    $Fixture = Get-PublicDomSnapshot -Label "malformed raw-text prefix fixture ($($FixtureSpec.Label))" -Html $FixtureHtml
    if ($null -ne $Fixture -and (
        $Fixture.H1Count -ne 0 `
            -or $Fixture.HasGuideShell `
            -or $Fixture.HasGuideNavigation `
            -or @($Fixture.AssetReferences.css).Count -ne 0 `
            -or @($Fixture.AssetReferences.js).Count -ne 0 `
            -or @($Fixture.Fragments).Count -ne 0 `
            -or @($Fixture.Ids).Count -ne 0
    )) {
        $Failures.Add("malformed raw-text tag prefix exposed inert descendants: $($FixtureSpec.Label)")
    }
}
$MalformedOpeningPrefixFixture = Get-PublicDomSnapshot -Label 'malformed opening prefix fixture' -Html '<html><body>< template><article data-vg-guide><h1>Real title</h1><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div></template></body></html>'
if ($null -ne $MalformedOpeningPrefixFixture -and (
    $MalformedOpeningPrefixFixture.H1Count -ne 1 `
        -or -not $MalformedOpeningPrefixFixture.HasGuideShell `
        -or -not $MalformedOpeningPrefixFixture.HasGuideNavigation `
        -or ($MalformedOpeningPrefixFixture.Fragments -join ',') -ne '#real' `
        -or ($MalformedOpeningPrefixFixture.Ids -join ',') -ne 'real'
)) {
    $Failures.Add('malformed opening tag prefix was treated as an inert tag')
}
$MalformedRawTextOpeningFixture = Get-PublicDomSnapshot -Label 'malformed raw-text opening fixture' -Html '<html><body>< script><article data-vg-guide><h1>Real title</h1><nav class="vg-guide-toc"><a href="#real">Real</a></nav><div id="real"></div><link rel="stylesheet" href="/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture"><script src="/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture"></script></article></script></body></html>'
if ($null -ne $MalformedRawTextOpeningFixture -and (
    $MalformedRawTextOpeningFixture.H1Count -ne 1 `
        -or -not $MalformedRawTextOpeningFixture.HasGuideShell `
        -or -not $MalformedRawTextOpeningFixture.HasGuideNavigation `
        -or ($MalformedRawTextOpeningFixture.AssetReferences.css -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=fixture' `
        -or ($MalformedRawTextOpeningFixture.AssetReferences.js -join ',') -ne '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js?ver=fixture' `
        -or ($MalformedRawTextOpeningFixture.Fragments -join ',') -ne '#real' `
        -or ($MalformedRawTextOpeningFixture.Ids -join ',') -ne 'real'
)) {
    $Failures.Add('malformed raw-text opening tag changed semantic guide inventory')
}
$OpeningSelfCloseProbe = '<vg-probe/>'
$OpeningSelfCloseSlashIndex = $OpeningSelfCloseProbe.IndexOf('/', [System.StringComparison]::Ordinal)
$OpeningSelfCloseDelimiterAccepted = Test-PublicHtmlTagNameDelimiter `
    -Html $OpeningSelfCloseProbe `
    -Index $OpeningSelfCloseSlashIndex `
    -IsClosing $false
$OpeningSelfCloseConverted = Convert-PublicHtmlForMshtml -Html $OpeningSelfCloseProbe -NavMarker 'self-close-probe'
if (-not $OpeningSelfCloseDelimiterAccepted -or $OpeningSelfCloseConverted -cne $OpeningSelfCloseProbe) {
    $Failures.Add('valid opening self-close slash delimiter was rejected')
}

$CssFixtureSpec = $ExpectedAssets.css
$FixtureOrigin = $BaseUri.GetLeftPart([System.UriPartial]::Authority).TrimEnd('/')
$ValidFixtureBuilder = [System.UriBuilder]::new($BaseUri)
$ValidFixtureBuilder.Path = $CssFixtureSpec.Path
$ValidFixtureBuilder.Query = 'ver=' + $CssFixtureSpec.Sha256
$ValidFixtureBuilder.Fragment = ''

$ExternalFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$ExternalFixtureBuilder.Host = if ($BaseUri.DnsSafeHost -ieq 'external.invalid') { 'other.invalid' } else { 'external.invalid' }
$SchemeFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$SchemeFixtureBuilder.Scheme = if ($BaseUri.Scheme -eq 'https') { 'http' } else { 'https' }
$SchemeFixtureBuilder.Port = $BaseUri.Port
$PortFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$PortFixtureBuilder.Port = if ($BaseUri.Port -eq 444) { 445 } else { 444 }
$CredentialFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$CredentialFixtureBuilder.UserName = 'user'
$CredentialFixtureBuilder.Password = 'pass'
$WrongPathFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$WrongPathFixtureBuilder.Path = '/wrong/guide-experience.css'
$WrongQueryFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$WrongQueryFixtureBuilder.Query = 'cache=1'
$WrongCaseQueryFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$WrongCaseQueryFixtureBuilder.Query = 'VER=1'
$StaleVersionFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$StaleVersionFixtureBuilder.Query = 'ver=0.1.0'
$WrongVersionFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$WrongVersionFixtureBuilder.Query = 'ver=' + ('0' * 64)
$FragmentFixtureBuilder = [System.UriBuilder]::new($ValidFixtureBuilder.Uri)
$FragmentFixtureBuilder.Fragment = 'fragment'

foreach ($UriFixture in @(
    @{ Url = $ExternalFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted external host' }
    @{ Url = $SchemeFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted scheme or port mismatch' }
    @{ Url = $PortFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted scheme or port mismatch' }
    @{ Url = $CredentialFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted credentials' }
    @{ Url = $WrongPathFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted wrong theme path' }
    @{ Url = $WrongQueryFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted invalid cache query or fragment' }
    @{ Url = $WrongCaseQueryFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted invalid cache query or fragment' }
    @{ Url = $StaleVersionFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted stale theme version' }
    @{ Url = $WrongVersionFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted wrong content version' }
    @{ Url = $FragmentFixtureBuilder.Uri.AbsoluteUri; Failure = 'asset URI fixture accepted invalid cache query or fragment' }
)) {
    $UriFixtureResult = Test-PublicAssetUri -PageUrl ($FixtureOrigin + '/pilot/') -Reference $UriFixture.Url -ExpectedAsset $CssFixtureSpec
    if ($null -eq $UriFixtureResult.Error) {
        $Failures.Add($UriFixture.Failure)
    }
}
$ValidUriFixture = Test-PublicAssetUri -PageUrl ($FixtureOrigin + '/pilot/') -Reference $ValidFixtureBuilder.Uri.AbsoluteUri -ExpectedAsset $CssFixtureSpec
if ($null -ne $ValidUriFixture.Error) {
    $Failures.Add("asset URI fixture rejected correct content version: $($ValidUriFixture.Error)")
}

$FixtureBytes = [System.Text.Encoding]::UTF8.GetBytes('reviewed fixture bytes')
$FixtureExpected = @{
    Sha256 = Get-ByteSha256 -Bytes $FixtureBytes
    AllowedContentTypes = @('text/css')
}
$FixtureUri = [uri]'https://vietnamguide.net/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css?ver=1'
if (@(Test-PublicAssetResponse -StatusCode 302 -ContentTypeHeader 'text/css' -Bytes $FixtureBytes -ResponseUri $FixtureUri -RequestedUri $FixtureUri -ExpectedAsset $FixtureExpected).Count -eq 0) {
    $Failures.Add('asset response fixture accepted redirect status')
}
$RedirectedFixtureUri = [uri]'https://vietnamguide.net/unexpected/guide-experience.css?ver=1'
if (@(Test-PublicAssetResponse -StatusCode 200 -ContentTypeHeader 'text/css' -Bytes $FixtureBytes -ResponseUri $RedirectedFixtureUri -RequestedUri $FixtureUri -ExpectedAsset $FixtureExpected).Count -eq 0) {
    $Failures.Add('asset response fixture accepted redirect status')
}
$HtmlErrorBytes = [System.Text.Encoding]::UTF8.GetBytes('<html><title>Error</title></html>')
if (@(Test-PublicAssetResponse -StatusCode 200 -ContentTypeHeader 'text/html; charset=UTF-8' -Bytes $FixtureBytes -ResponseUri $FixtureUri -RequestedUri $FixtureUri -ExpectedAsset $FixtureExpected).Count -eq 0) {
    $Failures.Add('asset response fixture accepted HTML error body or MIME')
}
if (@(Test-PublicAssetResponse -StatusCode 200 -ContentTypeHeader 'text/css' -Bytes $HtmlErrorBytes -ResponseUri $FixtureUri -RequestedUri $FixtureUri -ExpectedAsset $FixtureExpected).Count -eq 0) {
    $Failures.Add('asset response fixture accepted HTML error body or MIME')
}
$WrongHashExpected = @{ Sha256 = ('0' * 64); AllowedContentTypes = @('text/css') }
if (@(Test-PublicAssetResponse -StatusCode 200 -ContentTypeHeader 'text/css' -Bytes $FixtureBytes -ResponseUri $FixtureUri -RequestedUri $FixtureUri -ExpectedAsset $WrongHashExpected).Count -eq 0) {
    $Failures.Add('asset response fixture accepted wrong SHA-256')
}
$MissingAssetFixtureRejected = $false
try {
    [void](Get-PublicExpectedAssets -AssetsRoot (Join-Path ([System.IO.Path]::GetTempPath()) ([guid]::NewGuid().ToString('N'))))
}
catch {
    $MissingAssetFixtureRejected = $_.Exception.Message.Contains('local reviewed asset is missing')
}
if (-not $MissingAssetFixtureRejected) {
    $Failures.Add('local reviewed asset missing fixture did not fail closed')
}

if ($FixturesOnly) {
    if ($Failures.Count -gt 0) {
        $Failures | ForEach-Object { Write-Output "FAIL: $_" }
        exit 1
    }
    Write-Output "VietnamGuide public verifier fixtures passed for $BaseOriginKey."
    exit 0
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

    if ($null -eq $Page.Dom) {
        continue
    }
    if ($Page.Dom.H1Count -ne 1) {
        $Failures.Add("$($Page.Label): expected exactly one H1, found $($Page.Dom.H1Count) at $($Page.Url)")
    }
    if (-not $Page.Dom.HasGuideShell) {
        $Failures.Add("$($Page.Label): missing real guide shell at $($Page.Url)")
    }
    if (-not $Page.Dom.HasGuideNavigation) {
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
        [void](Get-PublicResource -Url $AssetUrl -Label "$($Page.Label) $($AssetSpec.Label)" -ExpectedAsset $ExpectedAssets[$AssetSpec.Kind])
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

    if ($null -ne $NonPilot.Dom) {
        if ($NonPilot.Dom.HasGuideShell) {
            $Failures.Add("$($NonPilot.Label): found unexpected real guide shell at $($NonPilot.Url)")
        }
        if ($NonPilot.Dom.HasGuideNavigation) {
            $Failures.Add("$($NonPilot.Label): found unexpected real guide navigation at $($NonPilot.Url)")
        }
        if (@($NonPilot.Dom.AssetReferences.css).Count -ne 0) {
            $Failures.Add("$($NonPilot.Label): found unexpected real guide CSS asset at $($NonPilot.Url)")
        }
        if (@($NonPilot.Dom.AssetReferences.js).Count -ne 0) {
            $Failures.Add("$($NonPilot.Label): found unexpected real guide JavaScript asset at $($NonPilot.Url)")
        }
    }
    Require-NoFatalText $NonPilot
}

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output "VietnamGuide public guide experience verification passed for $NormalizedBaseUrl."

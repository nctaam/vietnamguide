$ErrorActionPreference = 'Stop'

$PageUrl = 'https://vietnamguide.net/destinations/where-to-stay-in-ninh-binh/'
$HomeUrl = 'https://vietnamguide.net/'
$DestinationsUrl = 'https://vietnamguide.net/destinations/'
$SitemapUrl = 'https://vietnamguide.net/page-sitemap.xml'
$UserAgent = 'VietnamGuideQA/1.0'

function Get-VgPublicBody([string] $Url) {
    $response = Invoke-WebRequest `
        -Uri $Url `
        -UseBasicParsing `
        -Headers @{ 'Cache-Control' = 'no-cache'; 'User-Agent' = $UserAgent } `
        -TimeoutSec 45

    [pscustomobject] @{
        Status = [int] $response.StatusCode
        Body = [string] $response.Content
        FinalUrl = $response.BaseResponse.ResponseUri.AbsoluteUri
    }
}

function Require-True([string] $Label, [bool] $Condition) {
    if (-not $Condition) {
        throw $Label
    }
}

$page = Get-VgPublicBody $PageUrl
Require-True "Page HTTP status was $($page.Status)" ($page.Status -eq 200)

$canonicalMatch = [regex]::Match($page.Body, '<link[^>]+rel=["'']canonical["''][^>]+href=["'']([^"'']+)["'']', 'IgnoreCase')
if (-not $canonicalMatch.Success) {
    $canonicalMatch = [regex]::Match($page.Body, '<link[^>]+href=["'']([^"'']+)["''][^>]+rel=["'']canonical["'']', 'IgnoreCase')
}

$canonical = if ($canonicalMatch.Success) { $canonicalMatch.Groups[1].Value } else { '' }
Require-True "Canonical mismatch: $canonical" ($canonical -eq $PageUrl)

$robotsMatch = [regex]::Match($page.Body, '<meta[^>]+name=["'']robots["''][^>]+content=["'']([^"'']+)["'']', 'IgnoreCase')
$robots = if ($robotsMatch.Success) { $robotsMatch.Groups[1].Value } else { '' }
Require-True "Robots mismatch: $robots" ($robots -match 'index' -and $robots -match 'follow' -and $robots -notmatch 'noindex|nofollow')

$h1Count = ([regex]::Matches($page.Body, '<h1\b', 'IgnoreCase')).Count
Require-True "H1 count mismatch: $h1Count" ($h1Count -eq 1)

$sitemap = Get-VgPublicBody $SitemapUrl
$sitemapCount = ([regex]::Matches($sitemap.Body, [regex]::Escape($PageUrl))).Count
Require-True "Sitemap count mismatch: $sitemapCount" ($sitemapCount -eq 1)

$homePage = Get-VgPublicBody $HomeUrl
Require-True 'Homepage link missing' ($homePage.Body -match 'href=["'']/destinations/where-to-stay-in-ninh-binh/["'']')

$destinationsPage = Get-VgPublicBody $DestinationsUrl
Require-True 'Destinations hub link missing' ($destinationsPage.Body -match 'href=["'']/destinations/where-to-stay-in-ninh-binh/["'']')

$anchorMatches = [regex]::Matches($page.Body, '<a\b[^>]*\shref=(["''])(.*?)\1', 'IgnoreCase')
$externalHrefs = New-Object System.Collections.Generic.List[string]

foreach ($match in $anchorMatches) {
    $href = [System.Net.WebUtility]::HtmlDecode($match.Groups[2].Value.Trim())

    if ($href -eq '' -or $href.StartsWith('/') -or $href.StartsWith('#') -or $href -match '^(mailto|tel):') {
        continue
    }

    try {
        $uri = [Uri] $href

        if ($uri.Host -and $uri.Host -notin @('vietnamguide.net', 'www.vietnamguide.net')) {
            $externalHrefs.Add($href)
        }
    } catch {
    }
}

Require-True "External href budget exceeded: $($externalHrefs.Count) $($externalHrefs -join ' | ')" ($externalHrefs.Count -le 2)

$imageMatches = [regex]::Matches($page.Body, '<img\b[^>]*\ssrc=(["''])(.*?)\1', 'IgnoreCase')
$imageUrls = New-Object System.Collections.Generic.List[string]

foreach ($match in $imageMatches) {
    $src = [System.Net.WebUtility]::HtmlDecode($match.Groups[2].Value.Trim())

    if ($src -match 'upload\.wikimedia\.org') {
        $imageUrls.Add($src)
    }
}

$uniqueImages = @($imageUrls | Select-Object -Unique)
Require-True "Wikimedia image count too low: $($uniqueImages.Count)" ($uniqueImages.Count -ge 5)

$rangeStatuses = New-Object System.Collections.Generic.List[string]

foreach ($imageUrl in $uniqueImages) {
    $status = 0

    for ($attempt = 1; $attempt -le 6; $attempt++) {
        if ($attempt -gt 1) {
            Start-Sleep -Seconds (5 * $attempt)
        }

        $statusText = & curl.exe -L -s -A $UserAgent -o NUL -w '%{http_code}' -r 0-0 --max-time 45 $imageUrl
        $status = [int] $statusText

        if ($status -eq 429) {
            continue
        }

        break
    }

    Require-True "Image range check failed: $status $imageUrl" ($status -eq 206)
    $rangeStatuses.Add([string] $status)
}

Write-Output 'Where to Stay in Ninh Binh public QA passed.'
Write-Output "PageStatus=$($page.Status)"
Write-Output "Canonical=$canonical"
Write-Output "Robots=$robots"
Write-Output "H1Count=$h1Count"
Write-Output "SitemapCount=$sitemapCount"
Write-Output 'HomepageLink=present'
Write-Output 'DestinationsHubLink=present'
Write-Output "ExternalHrefCount=$($externalHrefs.Count)"
Write-Output "WikimediaImageCount=$($uniqueImages.Count)"
Write-Output "RangeStatuses=$($rangeStatuses -join ',')"

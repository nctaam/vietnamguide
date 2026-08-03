$ErrorActionPreference = 'Stop'

$PageUrl = 'https://vietnamguide.net/itineraries/14-days-in-vietnam/'
$HomeUrl = 'https://vietnamguide.net/'
$ItinerariesUrl = 'https://vietnamguide.net/itineraries/'
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

$h1Options = [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
$h1Matches = [regex]::Matches($page.Body, '<h1\b[^>]*>(.*?)</h1>', $h1Options)
$h1Count = $h1Matches.Count
$h1Text = if ($h1Count -eq 1) {
    [System.Net.WebUtility]::HtmlDecode(([regex]::Replace($h1Matches[0].Groups[1].Value, '<[^>]+>', '') -replace '\s+', ' ').Trim())
} else {
    ''
}

Require-True "H1 count mismatch: $h1Count" ($h1Count -eq 1)
Require-True "H1 text mismatch: $h1Text" ($h1Text -eq '14 Days in Vietnam: Best Two-Week Route by Month and Travel Style')

$requiredMarkers = @(
    'vg-itinerary-14day-hero:v1',
    'vg-itinerary-14day-concierge-verdict',
    'vg-itinerary-14day-at-a-glance:v1',
    'vg-itinerary-14day-source-diversity:v1',
    'vg-itinerary-14day-source-trail-snapshot:v1',
    'vg-itinerary-14day-route-chooser:v1',
    'vg-itinerary-14day-photo-grid:v1',
    'vg-itinerary-14day-route-builder',
    'vg-itinerary-14day-night-allocation',
    'vg-itinerary-14day-extra-days-value',
    'vg-itinerary-14day-pacing-map',
    'vg-itinerary-14day-transfer-pressure',
    'vg-itinerary-14day-route-variants',
    'vg-itinerary-14day-extension-matrix',
    'vg-itinerary-14day-base-strategy',
    'vg-itinerary-14day-season-pivots',
    'vg-itinerary-14day-slowdown-rules',
    'vg-itinerary-14day-audience-adaptations',
    'vg-itinerary-14day-prebook-flex',
    'vg-itinerary-14day-booking-sequence',
    'vg-itinerary-14day-planning-audit',
    'vg-itinerary-14day-faq',
    'vg-source-trail',
    'vg-update-log',
    'vg-related-routes'
)

foreach ($marker in $requiredMarkers) {
    Require-True "Rendered marker missing: $marker" ($page.Body.Contains($marker))
}

Require-True 'Route chooser phrase missing' ($page.Body.Contains('Start here: which 14-day route should you choose?'))
Require-True 'Source snapshot phrase missing' ($page.Body.Contains('Source trail snapshot for this two-week route'))
Require-True 'Text image credit phrase missing' ($page.Body.Contains('Alex 69200 vx, CC BY-SA 4.0') -and $page.Body.Contains('BacLuong, CC BY-SA 4.0'))
Require-True 'Anti-spam phrase missing' ($page.Body.Contains('This is not a maximum-coverage itinerary.'))

$sitemap = Get-VgPublicBody $SitemapUrl
$sitemapCount = ([regex]::Matches($sitemap.Body, [regex]::Escape($PageUrl))).Count
Require-True "Sitemap count mismatch: $sitemapCount" ($sitemapCount -eq 1)

$pageHrefPattern = 'href=["''](?:https://(?:www\.)?vietnamguide\.net)?/itineraries/14-days-in-vietnam/["'']'

$homePage = Get-VgPublicBody $HomeUrl
Require-True 'Homepage link missing' ($homePage.Body -match $pageHrefPattern)
Require-True 'Homepage linked Wikimedia credit anchors still present' ($homePage.Body -notmatch 'rel=["'']license noopener["'']')
Require-True 'Homepage text image credits missing' ($homePage.Body.Contains('Hero image: Ha Long Bay, Vietnam by Vyacheslav Argenberg, CC BY 4.0.') -and $homePage.Body.Contains('Image: Vivu Vietnam, CC BY-SA 4.0.'))

$itinerariesPage = Get-VgPublicBody $ItinerariesUrl
Require-True 'Itineraries hub link missing' ($itinerariesPage.Body -match $pageHrefPattern)

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
Require-True "Wikimedia image count too low: $($uniqueImages.Count)" ($uniqueImages.Count -ge 8)

$decodedBody = [System.Net.WebUtility]::HtmlDecode($page.Body)
$imageCreditPattern = '(Alex 69200 vx|Jakub Halun|CEphoto|Uwe Aranas|Wolkenkratzer|Steffen Schmitz|Vyacheslav Argenberg|BacLuong),\s*CC BY(?:-SA)? [0-9]\.0'
$imageCreditCount = ([regex]::Matches($decodedBody, $imageCreditPattern, 'IgnoreCase')).Count
Require-True "Image credit mention count too low: $imageCreditCount" ($imageCreditCount -ge 8)

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

Write-Output '14 Days in Vietnam public QA passed.'
Write-Output "PageStatus=$($page.Status)"
Write-Output "Canonical=$canonical"
Write-Output "Robots=$robots"
Write-Output "H1Count=$h1Count"
Write-Output "H1Text=$h1Text"
Write-Output "SitemapCount=$sitemapCount"
Write-Output 'HomepageLink=present'
Write-Output 'ItinerariesHubLink=present'
Write-Output "ExternalHrefCount=$($externalHrefs.Count)"
Write-Output "WikimediaImageCount=$($uniqueImages.Count)"
Write-Output "ImageCreditMentionCount=$imageCreditCount"
Write-Output "RangeStatuses=$($rangeStatuses -join ',')"

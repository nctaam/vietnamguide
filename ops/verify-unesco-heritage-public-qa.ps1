$ErrorActionPreference = 'Stop'

$PageUrl = 'https://vietnamguide.net/destinations/unesco-heritage-sites-vietnam/'
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

$titleMatch = [regex]::Match($page.Body, '<title>(.*?)</title>', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline)
$titleText = if ($titleMatch.Success) {
    [System.Net.WebUtility]::HtmlDecode(($titleMatch.Groups[1].Value -replace '\s+', ' ').Trim())
} else {
    ''
}

Require-True "SEO title mismatch: $titleText" ($titleText -eq 'UNESCO Heritage Sites in Vietnam: 2025 Route Guide')

$descriptionMatch = [regex]::Match($page.Body, '<meta[^>]+name=["'']description["''][^>]+content=["'']([^"'']+)["'']', 'IgnoreCase')
$descriptionText = if ($descriptionMatch.Success) {
    [System.Net.WebUtility]::HtmlDecode(($descriptionMatch.Groups[1].Value -replace '\s+', ' ').Trim())
} else {
    ''
}

Require-True "Meta description mismatch: $descriptionText" ($descriptionText -eq 'Choose which UNESCO World Heritage Sites in Vietnam fit your route, with 2025 updates, first-trip priorities, skip logic, source checks, and photo proof.')

$h1Options = [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
$h1Matches = [regex]::Matches($page.Body, '<h1\b[^>]*>(.*?)</h1>', $h1Options)
$h1Count = $h1Matches.Count
$h1Text = if ($h1Count -eq 1) {
    [System.Net.WebUtility]::HtmlDecode(([regex]::Replace($h1Matches[0].Groups[1].Value, '<[^>]+>', '') -replace '\s+', ' ').Trim())
} else {
    ''
}

Require-True "H1 count mismatch: $h1Count" ($h1Count -eq 1)
Require-True "H1 text mismatch: $h1Text" ($h1Text -eq 'UNESCO Heritage Sites in Vietnam: Which Ones Belong in Your Route')

$requiredMarkers = @(
    'vg-unesco-heritage-hero:v1',
    'vg-unesco-heritage-concierge-verdict',
    'vg-unesco-heritage-at-a-glance:v1',
    'vg-unesco-heritage-source-diversity:v1',
    'vg-unesco-heritage-source-trail-snapshot:v1',
    'vg-unesco-heritage-2025-updates:v1',
    'vg-unesco-heritage-route-chooser:v1',
    'vg-unesco-heritage-shortlist:v1',
    'vg-unesco-heritage-site-by-site:v1',
    'vg-unesco-heritage-route-map:v1',
    'vg-unesco-heritage-itinerary-length:v1',
    'vg-unesco-heritage-photo-grid:v1',
    'vg-unesco-heritage-skip-logic:v1',
    'vg-unesco-heritage-official-checks:v1',
    'vg-unesco-heritage-live-checks:v1',
    'vg-unesco-heritage-faq:v1',
    'vg-source-trail',
    'vg-update-log',
    'vg-related-routes'
)

foreach ($marker in $requiredMarkers) {
    Require-True "Rendered marker missing: $marker" ($page.Body.Contains($marker))
}

$decodedBody = [System.Net.WebUtility]::HtmlDecode($page.Body)
Require-True '2025 Yen Tu phrase missing' ($decodedBody -match 'Yen Tu\s+[\u002d\u2013]\s+Vinh Nghiem\s+[\u002d\u2013]\s+Con Son, Kiep Bac was inscribed in 2025')
Require-True '2025 Phong Nha phrase missing' ($page.Body.Contains('Phong Nha-Ke Bang National Park and Hin Nam No National Park is a 2025 transboundary update'))
Require-True '9-property category phrase missing' ($page.Body.Contains('9 UNESCO World Heritage properties') -and $page.Body.Contains('6 cultural, 2 natural, and 1 mixed'))
Require-True 'Source limitation phrase missing' ($page.Body.Contains('Sources can confirm official World Heritage status, categories, site names, inscription decisions, broad destination context, and image-license records; they cannot decide'))
Require-True 'Text image credit phrase missing' ($page.Body.Contains('Image credits are listed as text to keep the heritage decision guide readable and reduce visible outbound clutter.'))

$sitemap = Get-VgPublicBody $SitemapUrl
$sitemapCount = ([regex]::Matches($sitemap.Body, [regex]::Escape($PageUrl))).Count
Require-True "Sitemap count mismatch: $sitemapCount" ($sitemapCount -eq 1)

$pageHrefPattern = 'href=["''](?:https://(?:www\.)?vietnamguide\.net)?/destinations/unesco-heritage-sites-vietnam/["'']'

$homePage = Get-VgPublicBody $HomeUrl
Require-True 'Homepage link missing' ($homePage.Body -match $pageHrefPattern)

$destinationsPage = Get-VgPublicBody $DestinationsUrl
Require-True 'Destinations hub link missing' ($destinationsPage.Body -match $pageHrefPattern)

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
Require-True "Wikimedia image count too low: $($uniqueImages.Count)" ($uniqueImages.Count -ge 9)

$imageCreditPattern = '(Vyacheslav Argenberg|Jakub Halun|Christophe95|Loi Nguyen Duc|CEphoto|Uwe Aranas|Steffen Schmitz|Philip Nalangan|BacLuong|Bach Giang Nguyen),?\s*CC BY(?:-SA)? [0-9]\.0'
$imageCreditCount = ([regex]::Matches($decodedBody, $imageCreditPattern, 'IgnoreCase')).Count
Require-True "Image credit mention count too low: $imageCreditCount" ($imageCreditCount -ge 9)

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

Write-Output 'UNESCO Heritage Sites public QA passed.'
Write-Output "PageStatus=$($page.Status)"
Write-Output "Canonical=$canonical"
Write-Output "Robots=$robots"
Write-Output "SeoTitle=$titleText"
Write-Output "MetaDescription=$descriptionText"
Write-Output "H1Count=$h1Count"
Write-Output "H1Text=$h1Text"
Write-Output "SitemapCount=$sitemapCount"
Write-Output 'HomepageLink=present'
Write-Output 'DestinationsHubLink=present'
Write-Output "ExternalHrefCount=$($externalHrefs.Count)"
Write-Output "WikimediaImageCount=$($uniqueImages.Count)"
Write-Output "ImageCreditMentionCount=$imageCreditCount"
Write-Output "RangeStatuses=$($rangeStatuses -join ',')"

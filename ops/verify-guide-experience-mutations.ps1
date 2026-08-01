$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$VerifierRelativePath = 'ops/verify-guide-experience.ps1'
$RequiredContractPaths = @(
    'ops/verify-guide-experience.ps1'
    'ops/verify-guide-experience-mutations.ps1'
    'ops/verify-guide-experience-live.php'
    'ops/verify-guide-experience-public.ps1'
    'ops/verify-guide-experience-js-runtime.js'
    'wordpress/wp-content/themes/vietnamguide-premium/functions.php'
    'wordpress/wp-content/themes/vietnamguide-premium/footer.php'
    'wordpress/wp-content/themes/vietnamguide-premium/page.php'
    'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php'
    'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
    'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
    'wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php'
    'wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css'
    'wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
)

$Mutations = @(
    @{
        Name = 'page guide function availability guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/page.php'
        Find = @'
        $guideFunctionsReady = function_exists('vg_is_guide_experience_page')
            && function_exists('vg_build_guide_context')
            && function_exists('vg_is_valid_guide_context');
'@
        Replace = '        $guideFunctionsReady = true;'
    }
    @{
        Name = 'global reduced-motion scroll override removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css'
        Find = '  html {'
        Replace = '  html.vg-motion-disabled {'
    }
    @{
        Name = 'guide fragment heading offset removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css'
        Find = '  scroll-margin-top: calc(var(--vg-header-height) + 24px);'
        Replace = '  scroll-margin-top: calc(var(--vg-header-height) + 0px);'
    }
    @{
        Name = 'active guide aria-current assignment removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
        Find = "        target.link.setAttribute('aria-current', 'location');"
        Replace = "        target.link.removeAttribute('aria-current');"
    }
    @{
        Name = 'inactive guide aria-current cleanup removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
        Find = "        target.link.removeAttribute('aria-current');"
        Replace = "        target.link.setAttribute('aria-current', 'location');"
    }
    @{
        Name = 'non-H2 ID reservation removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = '        if (! $isTagCloser) {'
        Replace = '        if (! $isTagCloser && ''H2'' === $tokenName) {'
    }
    @{
        Name = 'public guide asset status guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if ($StatusCode -ne 200) {'
        Replace = 'if ($StatusCode -lt 0) {'
    }
    @{
        Name = 'public semantic H1 guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if ($Page.Dom.H1Count -ne 1) {'
        Replace = 'if ($Page.Dom.H1Count -lt 0) {'
    }
    @{
        Name = 'public semantic guide shell guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if (-not $Page.Dom.HasGuideShell) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public semantic guide navigation guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if (-not $Page.Dom.HasGuideNavigation) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public DOM snapshot reuse removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = '$Snapshot = $Page.Dom'
        Replace = '$Snapshot = Get-PublicDomSnapshot -Html $Page.Content -Label $Page.Label'
    }
    @{
        Name = 'public inert raw-text tokenizer state removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "`$RawTextTags = @('iframe', 'noembed', 'noframes', 'plaintext', 'script', 'style', 'textarea', 'title', 'xmp')"
        Replace = "`$RawTextTags = @('iframe', 'noembed', 'noframes', 'plaintext', 'style', 'textarea', 'title', 'xmp')"
    }
    @{
        Name = 'public script double-escaped state removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "`$State = 'double-escaped'"
        Replace = "`$State = 'escaped'"
    }
    @{
        Name = 'public HTML tag-name delimiter validation removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "return -not `$IsClosing -and `$Character -eq '/'"
        Replace = 'return $true'
    }
    @{
        Name = 'public opening self-close delimiter rejection'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "return -not `$IsClosing -and `$Character -eq '/'"
        Replace = 'return $false'
    }
    @{
        Name = 'public HTML tag-prefix grammar relaxation'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "`$TagMatch = [regex]::Match(`$Token, '^<(?<closing>/)?(?<name>[A-Za-z][A-Za-z0-9:-]*)', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)"
        Replace = "`$TagMatch = [regex]::Match(`$Token, '^<\s*(?<closing>/?)\s*(?<name>[A-Za-z][A-Za-z0-9:-]*)', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)"
    }
    @{
        Name = 'public custom-origin fixture isolation removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "`$FixtureOrigin = `$BaseUri.GetLeftPart([System.UriPartial]::Authority).TrimEnd('/')"
        Replace = "`$FixtureOrigin = 'https://vietnamguide.net'"
    }
    @{
        Name = 'public semantic H1 inventory removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "H1Count = @(`$Document.getElementsByTagName('h1')).Count"
        Replace = "H1Count = [regex]::Matches(`$Html, '<h1\b').Count"
    }
    @{
        Name = 'public semantic guide shell inventory removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "if (`$null -ne `$Element.getAttributeNode('data-vg-guide')) {"
        Replace = 'if ($true) {'
    }
    @{
        Name = 'public semantic guide navigation inventory removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "foreach (`$Navigation in `$Document.getElementsByTagName('div')) {"
        Replace = 'foreach ($Navigation in @()) {'
    }
    @{
        Name = 'public semantic stylesheet element inventory removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "foreach (`$Link in `$Document.getElementsByTagName('link')) {"
        Replace = 'foreach ($Link in @()) {'
    }
    @{
        Name = 'public semantic script element inventory removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "foreach (`$Script in `$Document.getElementsByTagName('script')) {"
        Replace = 'foreach ($Script in @()) {'
    }
    @{
        Name = 'public semantic stylesheet relation guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "`$IsStylesheet = `$RelTokens -contains 'stylesheet'"
        Replace = '$IsStylesheet = $true'
    }
    @{
        Name = 'public asset exact origin guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if ((Get-NormalizedOriginKey $Resolved) -ne $BaseOriginKey) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public asset credentials guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if (-not [string]::IsNullOrEmpty($Resolved.UserInfo)) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public asset exact path guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if ($Resolved.AbsolutePath -cne $ExpectedAsset.Path) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public asset cache query guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "if (`$Resolved.Query -ne '' -and `$Resolved.Query -cnotmatch '^\?ver=[A-Za-z0-9._-]+`$') {"
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public asset fragment guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "if (`$Resolved.Fragment -ne '') {"
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public asset redirect rejection removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = '-MaximumRedirection 0'
        Replace = '-MaximumRedirection 5'
    }
    @{
        Name = 'public asset MIME guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if ($AllowedContentTypes -notcontains $ContentType) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public asset response URI guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if ($ResponseUri.AbsoluteUri -ne $RequestedUri.AbsoluteUri) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public asset SHA-256 parity guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if (-not $ActualHash.Equals($ExpectedAsset.Sha256, [System.StringComparison]::OrdinalIgnoreCase)) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public local asset fail-closed guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if (-not (Test-Path -LiteralPath $LocalPath -PathType Leaf)) {'
        Replace = 'if ($false) {'
    }
    @{
        Name = 'public guide fragment target guard removal'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = 'if ($TargetCount -ne 1) {'
        Replace = 'if ($TargetCount -lt 0) {'
    }
    @{
        Name = 'public non-pilot inventory regression'
        File = 'ops/verify-guide-experience-public.ps1'
        Find = "    'destinations/hanoi-travel-guide'"
        Replace = "    'about'"
    }
    @{
        Name = 'leading comment-only freeform rejection'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = "`$without_comments = preg_replace('/<!--[\s\S]*?-->/', '', `$html);"
        Replace = '$without_comments = $html;'
    }
    @{
        Name = 'meaningful leading freeform acceptance'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = "return is_string(`$without_comments) && trim(`$without_comments) === '';"
        Replace = 'return true;'
    }
    @{
        Name = 'pilot allowlist bypass'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php'
        Find = @'
    if (! in_array($path, vg_guide_pilot_paths(), true)) {
        return false;
    }
'@
        Replace = @'
    if (false) {
        return false;
    }
'@
    }
    @{
        Name = 'rendered hero H1 guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = '$heroStats[''h1_count''] !== 1'
        Replace = '$heroStats[''h1_count''] < 0'
    }
    @{
        Name = 'rendered body H1 guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = '$bodyStats[''h1_count''] !== 0'
        Replace = '$bodyStats[''h1_count''] < 0'
    }
    @{
        Name = 'required hero class guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = '|| ! $heroStats[''has_hero_class'']'
        Replace = '|| false'
    }
    @{
        Name = 'heading HTML round-trip guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = '$preparedBody[''html''] !== $context[''body_html'']'
        Replace = 'false'
    }
    @{
        Name = 'heading list round-trip guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = '$preparedBody[''headings''] !== $context[''headings'']'
        Replace = 'false'
    }
    @{
        Name = 'unique heading ID guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = '|| isset($headingIds[$headingId])'
        Replace = '|| false'
    }
    @{
        Name = 'opted-out heading inclusion'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = '$eligible = ! $heading[''opt_out''] && $label !== '''';'
        Replace = '$eligible = $label !== '''';'
    }
    @{
        Name = 'reserved heading collision guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = 'while (isset($reservedIds[$candidate]) || isset($assignedIds[$candidate])) {'
        Replace = 'while (isset($assignedIds[$candidate])) {'
    }
    @{
        Name = 'assigned heading collision guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = 'while (isset($reservedIds[$candidate]) || isset($assignedIds[$candidate])) {'
        Replace = 'while (isset($reservedIds[$candidate])) {'
    }
    @{
        Name = 'incomplete rendered HTML guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = @'
    if ($processor->paused_at_incomplete_token()) {
        return null;
    }
'@
        Replace = @'
    if (false) {
        return null;
    }
'@
    }
    @{
        Name = 'incomplete heading-plan guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = 'if ($processor->paused_at_incomplete_token() || $currentHeading !== null) {'
        Replace = 'if ($currentHeading !== null) {'
    }
    @{
        Name = 'heading application count guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php'
        Find = 'if ($processor->paused_at_incomplete_token() || $headingIndex !== count($plan)) {'
        Replace = 'if ($processor->paused_at_incomplete_token()) {'
    }
    @{
        Name = 'duplicate literal guide H1'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php'
        Find = @'
?>
<article
'@
        Replace = @'
?>
<h1>Duplicate guide title</h1>
<article
'@
    }
    @{
        Name = 'global guide asset enqueue'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/functions.php'
        Find = 'if (vg_is_guide_experience_page()) {'
        Replace = 'if (true) {'
    }
    @{
        Name = 'legacy table wrapper removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
        Find = '    wrapper.appendChild(table);'
        Replace = '    table.parentNode.removeChild(wrapper);'
    }
    @{
        Name = 'legacy table idempotence guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
        Find = @'
    if (
      table.parentElement &&
      table.parentElement.classList.contains('vg-decision-table__scroll')
    ) {
      return;
    }
'@
        Replace = @'
    if (false) {
      return;
    }
'@
    }
    @{
        Name = 'legacy table focus guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
        Find = @'
    if (!scrollContainer.hasAttribute('tabindex')) {
      scrollContainer.setAttribute('tabindex', '0');
    }
'@
        Replace = @'
    scrollContainer.removeAttribute('tabindex');
'@
    }
    @{
        Name = 'legacy table preventDefault regression'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js'
        Find = '    var hash = link.getAttribute(''href'');'
        Replace = "    event.preventDefault();`n    var hash = link.getAttribute('href');"
    }
    @{
        Name = 'canonical TOC relationship removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = 'if ($context[''toc_html''] !== vg_render_guide_toc($context[''headings''])) {'
        Replace = 'if (false) {'
    }
    @{
        Name = 'EEAT reviewed precedence inversion'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = @'
    $reviewed = '';
    if (function_exists('vg_eeat_get_field')) {
        $reviewed = trim((string) vg_eeat_get_field($post->ID, 'last_meaningful_update'));
    }
    if ($reviewed === '') {
        $reviewed = trim((string) get_post_meta($post->ID, '_vg_reviewed_at', true));
    }
'@
        Replace = @'
    $reviewed = trim((string) get_post_meta($post->ID, '_vg_reviewed_at', true));
    if ($reviewed === '' && function_exists('vg_eeat_get_field')) {
        $reviewed = trim((string) vg_eeat_get_field($post->ID, 'last_meaningful_update'));
    }
'@
    }
    @{
        Name = 'invalid curated URL shape guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = 'if (! $isRootRelative && ! $isProtocolRelative && ! $isAbsoluteWeb) {'
        Replace = 'if (false) {'
    }
    @{
        Name = 'invalid curated route item guard removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = 'if ($title === '''' || $url === '''') {'
        Replace = 'if (false) {'
    }
    @{
        Name = 'existing related-route suppression removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = @'
    if ($hasExisting) {
        return [];
    }
'@
        Replace = @'
    if (false) {
        return [];
    }
'@
    }
    @{
        Name = 'existing related-route context invariant removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = 'if ($hasExistingRelated && $context[''related_routes''] !== []) {'
        Replace = 'if (false) {'
    }
    @{
        Name = 'password-protected fallback removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = @'
    if ($post->post_password !== '' || post_password_required($post)) {
        return null;
    }
'@
        Replace = @'
    if (false) {
        return null;
    }
'@
    }
    @{
        Name = 'multipage fallback removal'
        File = 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-context.php'
        Find = @'
    if (preg_match('/<!--\s*nextpage\s*-->/i', $post->post_content) === 1) {
        return null;
    }
'@
        Replace = @'
    if (false) {
        return null;
    }
'@
    }
)

function Copy-ContractTree {
    param([string]$DestinationRoot)

    foreach ($RelativePath in $RequiredContractPaths) {
        $SourcePath = Join-Path $RepoRoot $RelativePath
        if (-not (Test-Path -LiteralPath $SourcePath -PathType Leaf)) {
            throw "Required contract file is missing: $RelativePath"
        }

        $DestinationPath = Join-Path $DestinationRoot $RelativePath
        $DestinationDirectory = Split-Path -Parent $DestinationPath
        $null = New-Item -ItemType Directory -Path $DestinationDirectory -Force
        Copy-Item -LiteralPath $SourcePath -Destination $DestinationPath
    }
}

function Convert-MutationTextToSourceStyle {
    param(
        [string]$Text,
        [string]$LineEnding
    )

    $Normalized = $Text -replace "`r`n?", "`n"
    if ($Normalized.StartsWith("`n")) {
        $Normalized = $Normalized.Substring(1)
    }

    return $Normalized.Replace("`n", $LineEnding)
}

function Set-ExactReplacement {
    param(
        [string]$Root,
        [string]$RelativePath,
        [string]$Find,
        [string]$Replace
    )

    $Path = Join-Path $Root $RelativePath
    $Content = [System.IO.File]::ReadAllText($Path)
    $LineEnding = if ($Content.Contains("`r`n")) { "`r`n" } else { "`n" }
    $FindForMatching = Convert-MutationTextToSourceStyle -Text $Find -LineEnding $LineEnding
    $ReplaceForWriting = Convert-MutationTextToSourceStyle -Text $Replace -LineEnding $LineEnding
    $FirstIndex = $Content.IndexOf($FindForMatching, [System.StringComparison]::Ordinal)
    $SecondIndex = if ($FirstIndex -ge 0) {
        $Content.IndexOf($FindForMatching, $FirstIndex + $FindForMatching.Length, [System.StringComparison]::Ordinal)
    } else {
        -1
    }

    if ($FirstIndex -lt 0 -or $SecondIndex -ge 0) {
        throw "Mutation target must occur exactly once in ${RelativePath}: $Find"
    }

    $Updated = $Content.Substring(0, $FirstIndex) + $ReplaceForWriting + $Content.Substring($FirstIndex + $FindForMatching.Length)
    [System.IO.File]::WriteAllText($Path, $Updated, [System.Text.UTF8Encoding]::new($false))
}

function Test-SetExactReplacementPortableFixture {
    $FixtureRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('vietnamguide-set-exact-' + [guid]::NewGuid().ToString('N'))
    try {
        $null = New-Item -ItemType Directory -Path $FixtureRoot
        $FixturePath = Join-Path $FixtureRoot 'fixture.txt'
        [System.IO.File]::WriteAllText(
            $FixturePath,
            "alpha`r`nbeta`r`ngamma`r`n",
            [System.Text.UTF8Encoding]::new($false)
        )

        Set-ExactReplacement -Root $FixtureRoot -RelativePath 'fixture.txt' -Find @'
beta
gamma
'@ -Replace @'
delta
epsilon
'@

        $Actual = [System.IO.File]::ReadAllText($FixturePath)
        if ($Actual -ne "alpha`r`ndelta`r`nepsilon`r`n") {
            throw "Portable Set-ExactReplacement fixture produced unexpected content: $Actual"
        }
    } finally {
        if (Test-Path -LiteralPath $FixtureRoot -PathType Container) {
            Remove-Item -LiteralPath $FixtureRoot -Recurse -Force
        }
    }
}

Test-SetExactReplacementPortableFixture

$TempBase = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath()).TrimEnd(
    [System.IO.Path]::DirectorySeparatorChar,
    [System.IO.Path]::AltDirectorySeparatorChar
)
$TempLeaf = 'vietnamguide-guide-mutations-' + [guid]::NewGuid().ToString('N')
$TempRoot = Join-Path $TempBase $TempLeaf
$ValidatedTempRoot = $null
$Failures = [System.Collections.Generic.List[string]]::new()

try {
    $null = New-Item -ItemType Directory -Path $TempRoot
    $ValidatedTempRoot = (Resolve-Path -LiteralPath $TempRoot).Path
    $ExpectedPrefix = $TempBase + [System.IO.Path]::DirectorySeparatorChar
    if (-not $ValidatedTempRoot.StartsWith($ExpectedPrefix, [System.StringComparison]::OrdinalIgnoreCase) -or (Split-Path -Leaf $ValidatedTempRoot) -ne $TempLeaf) {
        throw "Refusing to use unvalidated mutation temp directory: $ValidatedTempRoot"
    }

    $BaselineRoot = Join-Path $ValidatedTempRoot 'baseline'
    Copy-ContractTree $BaselineRoot
    $BaselineOutput = & powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $BaselineRoot $VerifierRelativePath) -RepoRootOverride $BaselineRoot 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "Copied contract baseline failed before mutation testing:`n$($BaselineOutput -join "`n")"
    }

    foreach ($Mutation in $Mutations) {
        $MutationLeaf = [regex]::Replace($Mutation.Name, '[^A-Za-z0-9]+', '-').Trim('-').ToLowerInvariant()
        $MutationRoot = Join-Path $ValidatedTempRoot $MutationLeaf
        Copy-ContractTree $MutationRoot
        Set-ExactReplacement `
            -Root $MutationRoot `
            -RelativePath $Mutation.File `
            -Find $Mutation.Find `
            -Replace $Mutation.Replace

        $MutationOutput = & powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $MutationRoot $VerifierRelativePath) -RepoRootOverride $MutationRoot 2>&1
        if ($LASTEXITCODE -eq 0) {
            $Failures.Add("Mutation was not rejected: $($Mutation.Name)")
            continue
        }

        if (-not (($MutationOutput -join "`n").Contains('FAIL:'))) {
            $Failures.Add("Mutation did not produce a verifier failure: $($Mutation.Name)`n$($MutationOutput -join "`n")")
            continue
        }

        Write-Output "PASS: mutation rejected - $($Mutation.Name)"
    }
} finally {
    if ($null -ne $ValidatedTempRoot -and (Test-Path -LiteralPath $ValidatedTempRoot -PathType Container)) {
        Remove-Item -LiteralPath $ValidatedTempRoot -Recurse -Force
    }
}

if ($Failures.Count -gt 0) {
    $Failures | ForEach-Object { Write-Output "FAIL: $_" }
    exit 1
}

Write-Output "VietnamGuide guide experience mutation checks passed ($($Mutations.Count) rejected mutations)."

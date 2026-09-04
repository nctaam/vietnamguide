$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$verifierRelative = 'ops\verify-core-mu-plugin.ps1'
$pluginRelative = 'wordpress\wp-content\mu-plugins\vietnamguide-core.php'
$themeRelative = 'wordpress\wp-content\themes\vietnamguide-premium'

function Invoke-CoreMuVerifier {
    param(
        [string]$Root,
        [string]$Label
    )

    $previousErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $Root $verifierRelative) 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorAction
    }
    if ($exitCode -ne 0) {
        throw "$Label failed with exit ${exitCode}:`n$($output -join "`n")"
    }
}

$productionPlugin = [System.IO.File]::ReadAllText((Join-Path $repoRoot $pluginRelative))
if ($productionPlugin.Contains("`r`n") -or -not $productionPlugin.Contains("`n")) {
    throw 'Production portability fixture must use LF line endings.'
}
Invoke-CoreMuVerifier -Root $repoRoot -Label 'LF core MU verifier fixture'

$tempParent = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempLeaf = 'vietnamguide-core-mu-portability-' + [guid]::NewGuid().ToString('N')
$tempRoot = Join-Path $tempParent $tempLeaf
$validatedTempRoot = $null
try {
    $null = New-Item -ItemType Directory -Path (Join-Path $tempRoot 'ops') -Force
    $null = New-Item -ItemType Directory -Path (Join-Path $tempRoot 'wordpress\wp-content\mu-plugins') -Force
    $null = New-Item -ItemType Directory -Path (Join-Path $tempRoot 'wordpress\wp-content\themes') -Force
    $validatedTempRoot = (Resolve-Path -LiteralPath $tempRoot).Path
    $expectedPrefix = $tempParent.TrimEnd([char[]]'\/') + [System.IO.Path]::DirectorySeparatorChar
    if (
        -not $validatedTempRoot.StartsWith($expectedPrefix, [System.StringComparison]::OrdinalIgnoreCase) -or
        (Split-Path -Leaf $validatedTempRoot) -cne $tempLeaf
    ) {
        throw "Refusing unsafe temp directory: $validatedTempRoot"
    }

    Copy-Item -LiteralPath (Join-Path $repoRoot $verifierRelative) -Destination (Join-Path $validatedTempRoot $verifierRelative)
    Copy-Item -LiteralPath (Join-Path $repoRoot $themeRelative) -Destination (Join-Path $validatedTempRoot 'wordpress\wp-content\themes') -Recurse

    $crlfPlugin = [regex]::Replace($productionPlugin, "`r`n|`r|`n", "`r`n")
    [System.IO.File]::WriteAllText(
        (Join-Path $validatedTempRoot $pluginRelative),
        $crlfPlugin,
        [System.Text.UTF8Encoding]::new($false)
    )
    Invoke-CoreMuVerifier -Root $validatedTempRoot -Label 'CRLF core MU verifier fixture'
} finally {
    if ($null -ne $validatedTempRoot -and (Test-Path -LiteralPath $validatedTempRoot -PathType Container)) {
        Remove-Item -LiteralPath $validatedTempRoot -Recurse -Force
    }
}

Write-Output 'VietnamGuide core MU verifier portability checks passed for LF and CRLF.'

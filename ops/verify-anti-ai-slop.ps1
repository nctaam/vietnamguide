param(
    [switch]$SelfTest,
    [string]$Url = '',
    [string]$File = '',
    [switch]$ProductionScan,
    [string]$OutFile = ''
)

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$LinterPy = Join-Path $ScriptDir 'anti-ai-slop-linter.py'
$TestPy = Join-Path $ScriptDir 'tests\test-anti-ai-slop.py'

Write-Output "=== VietnamGuide Anti-AI Slop Quality Verifier ==="

if ($SelfTest) {
    Write-Output "Running Anti-AI Slop unit tests..."
    python $TestPy
    if ($LASTEXITCODE -ne 0) {
        throw "Unit tests failed with exit code $LASTEXITCODE"
    }
    Write-Output "VietnamGuide Anti-AI Slop self-test passed."
    exit 0
}

if ($Url -ne '') {
    Write-Output "Scanning URL: $Url"
    python $LinterPy --url $Url
    if ($LASTEXITCODE -ne 0) {
        throw "URL scan failed with exit code $LASTEXITCODE"
    }
    exit 0
}

if ($File -ne '') {
    Write-Output "Scanning File: $File"
    python $LinterPy --file $File
    if ($LASTEXITCODE -ne 0) {
        throw "File scan failed with exit code $LASTEXITCODE"
    }
    exit 0
}

if ($ProductionScan) {
    Write-Output "Running full production sitemap crawl..."
    $DefaultOut = Join-Path $ScriptDir 'reports\anti-ai-slop-audit-latest.json'
    $TargetOut = if ($OutFile -ne '') { $OutFile } else { $DefaultOut }
    
    python $LinterPy --crawl-sitemap https://vietnamguide.net/sitemap_index.xml --out $TargetOut
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Production crawl found quality issues. Review report at: $TargetOut"
        exit 1
    }
    Write-Output "VietnamGuide production Anti-AI Slop verification passed."
    exit 0
}

# Default behavior: run self-test
Write-Output "No parameters specified. Running self-test by default..."
python $TestPy
if ($LASTEXITCODE -ne 0) {
    throw "Unit tests failed with exit code $LASTEXITCODE"
}
Write-Output "VietnamGuide Anti-AI Slop checks passed."
exit 0

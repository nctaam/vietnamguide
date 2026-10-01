param(
    [switch]$WithMutations,
    [switch]$WithRemote,
    [switch]$Deploy,
    [switch]$Full
)

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Output "========================================================"
Write-Output "   VietnamGuide CI/CD Master Quality Gate Orchestrator   "
Write-Output "========================================================"

# Gate 1: Anti-AI Slop Quality Gate (Self-Test v3.0)
Write-Output "`n[GATE 1/8] Anti-AI Slop Quality Engine v3.0..."
& powershell -File (Join-Path $ScriptDir 'verify-anti-ai-slop.ps1') -SelfTest
if ($LASTEXITCODE -ne 0) { throw "Gate 1 (Anti-AI Slop) failed!" }

# Gate 2: Core MU-Plugin Invariant & Contracts
Write-Output "`n[GATE 2/8] Core MU-Plugin Invariant & Safety Contracts..."
& powershell -File (Join-Path $ScriptDir 'verify-core-mu-plugin.ps1')
if ($LASTEXITCODE -ne 0) { throw "Gate 2 (Core MU-Plugin) failed!" }

# Gate 3: Gutenberg Core Block Patterns
Write-Output "`n[GATE 3/8] Gutenberg Core Block Patterns..."
& powershell -File (Join-Path $ScriptDir 'verify-core-block-patterns.ps1')
if ($LASTEXITCODE -ne 0) { throw "Gate 3 (Core Block Patterns) failed!" }

# Gate 4: Theme Structure & CSS Verification
Write-Output "`n[GATE 4/8] Theme Structure & CSS Verification..."
& powershell -File (Join-Path $ScriptDir 'verify-homepage-theme.ps1')
if ($LASTEXITCODE -ne 0) { throw "Gate 4 (Homepage Theme) failed!" }

# Gate 5: Interactive Shortcodes Unit Tests (A11y, Continuity, Synergy)
Write-Output "`n[GATE 5/8] Interactive Shortcodes & A11y / State Continuity..."
python (Join-Path $ScriptDir 'tests\test_interactive_shortcodes.py')
if ($LASTEXITCODE -ne 0) { throw "Gate 5 (Interactive Shortcodes) failed!" }

# Gate 6: Canonical Route Registry, AIO Inventory & Runtime Fallback Contracts
Write-Output "`n[GATE 6/8] Route Registry, AIO & Runtime Fallback Contracts..."
python -m unittest (Join-Path $ScriptDir 'tests\test_route_contract.py') -v
if ($LASTEXITCODE -ne 0) { throw "Gate 6 (Route Contract) failed!" }

# Gate 7: Deployment Configuration & SSH Trust Contracts
Write-Output "`n[GATE 7/8] Deployment Configuration & SSH Trust Contracts..."
python -m unittest (Join-Path $ScriptDir 'tests\test_deploy_config.py') (Join-Path $ScriptDir 'tests\test_deploy_security_audit.py') -v
if ($LASTEXITCODE -ne 0) { throw "Gate 7 (Deployment Configuration) failed!" }

# Gate 8: Deployment Surface Static Security Audit
Write-Output "`n[GATE 8/8] Deployment Surface Static Security Audit..."
python (Join-Path $ScriptDir 'deploy_security_audit.py') --root $ScriptDir --release-path 'deploy_theme_updates.py' --strict-release
if ($LASTEXITCODE -ne 0) { throw "Gate 8 (Deployment Surface Security) failed!" }

# Optional Gate: AST Mutation Suite (112 mutations)
if ($WithMutations -or $Full) {
    Write-Output "`n[EXTENDED GATE] Guide Experience AST Mutation Suite (112 mutations)..."
    & powershell -File (Join-Path $ScriptDir 'verify-guide-experience-mutations.ps1')
    if ($LASTEXITCODE -ne 0) { throw "AST Mutation Gate failed!" }
}

# Optional Deployment Step
if ($Deploy -or $Full) {
    Write-Output "`n[DEPLOYMENT] Deploying Theme Updates to Production VPS..."
    python (Join-Path $ScriptDir 'deploy_theme_updates.py')
    if ($LASTEXITCODE -ne 0) { throw "Deployment failed!" }
}

# Optional Remote Verification: Public HTTPS Routes
if ($WithRemote -or $Full) {
    Write-Output "`n[REMOTE GATE] Verifying Public HTTPS Routes..."
    & powershell.exe -File (Join-Path $ScriptDir 'verify-guide-experience-public.ps1')
    if ($LASTEXITCODE -ne 0) { throw "Remote Public Routes Gate failed!" }
}

Write-Output "`n========================================================"
Write-Output "   ALL VIETNAMGUIDE QUALITY GATES PASSED SUCCESSFULLY   "
Write-Output "========================================================"
exit 0

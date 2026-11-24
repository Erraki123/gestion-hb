$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $root
if (-not (Test-Path '.\artisan')) { throw 'Run from your Laravel project' }
if (-not (Test-Path '.\tools\atlas_smart_select.py')) { throw 'Phase 6F missing' }
if (-not (Test-Path '.\tools\atlas_contact_merge.py')) { throw 'Phase 6K missing' }
if (-not (Test-Path '.\tools\atlas_local_digest.py')) { throw 'Phase 6P missing' }
python -m unittest discover -s '.\tests' -p 'test_atlas_daily_monitor.py' -v
if ($LASTEXITCODE -ne 0) { throw 'Phase 6Q tests failed' }
Write-Host 'Phase 6Q ready. No scheduled task was created.' -ForegroundColor Green
Write-Host 'Run: powershell -ExecutionPolicy Bypass -File .\RUN_ATLAS_PHASE6Q.ps1'

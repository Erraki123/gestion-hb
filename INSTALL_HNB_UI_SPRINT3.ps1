$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
if (-not (Test-Path '.\artisan')) { throw 'Run from the Laravel project root.' }
python .\APPLY_HNB_UI_SPRINT3.py
if ($LASTEXITCODE -ne 0) { throw 'UI Sprint3 patch failed. Review the message above.' }
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'Laravel view:clear failed.' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Laravel view:cache failed. Restore the Blade files from backup-hnb-ui-sprint3.' }
Write-Host 'UI SPRINT 3 READY. Open /admin/dashboard and /prospects (Ctrl+F5).' -ForegroundColor Green

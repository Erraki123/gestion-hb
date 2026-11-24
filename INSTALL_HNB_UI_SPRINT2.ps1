$ErrorActionPreference='Stop'
Set-Location $PSScriptRoot
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root.' }
python .\APPLY_HNB_UI_SPRINT2.py
if ($LASTEXITCODE -ne 0) { throw 'Patch failed. No Laravel commands executed.' }
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'view:cache failed; restore views using backup-hnb-ui-sprint2 folder' }
Write-Host 'HNB UI Sprint2 ready. Open /admin/dashboard and /prospects, Ctrl+F5.' -ForegroundColor Green

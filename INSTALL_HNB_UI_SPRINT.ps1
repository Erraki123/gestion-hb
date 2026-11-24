$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
if (-not (Test-Path '.\artisan')) { throw 'Run this installer in the Laravel project root.' }
python .\tools\apply_ui_finish.py
if ($LASTEXITCODE -ne 0) { throw 'UI patch failed; check the error above.' }
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'View cache clear failed.' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Blade compile failed.' }
Write-Host 'UI patch installed. Next: npm run build' -ForegroundColor Green

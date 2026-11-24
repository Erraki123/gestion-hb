$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root.' }
python '.\HNB_CHROME_LEGACY_BRIDGE\APPLY_HNB_CHROME_BRIDGE.py'
if ($LASTEXITCODE -ne 0) { throw 'Bridge installation failed; inspect message above.' }
php artisan route:list --path=pdf-studio -v
php artisan route:list --path=quotes
Write-Host 'Test /quotes/50/pdf and /invoices/29/pdf with authorized Admin.' -ForegroundColor Green

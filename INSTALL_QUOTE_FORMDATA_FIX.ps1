$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run this command from the Laravel project root.' }
python .\APPLY_QUOTE_FORMDATA_FIX.py
if ($LASTEXITCODE -ne 0) { throw 'Patch failed.' }
php -l .\app\Http\Controllers\QuoteController.php
if ($LASTEXITCODE -ne 0) { throw 'PHP syntax check failed. Restore QuoteController.php from backup.' }
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'Blade cache clearing failed.' }
Write-Host 'Quote formData hotfix complete. Test /quotes/create and /quotes/{id}/edit.' -ForegroundColor Green

$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Open PowerShell in Laravel root (artisan missing).' }
$required = @('PATCH/app/Services/ProspectDuplicateService.php', 'PATCH/tests/Unit/ProspectDuplicateServiceTest.php', 'APPLY_PHASE6C.php')
foreach ($f in $required) { if (-not (Test-Path $f)) { throw "Missing: $f" } }
$svc = 'app/Services/ProspectDuplicateService.php'
$test = 'tests/Unit/ProspectDuplicateServiceTest.php'
if (Test-Path $svc) { throw 'Service already exists. Review files before reinstalling.' }
New-Item -ItemType Directory -Force -Path (Split-Path $svc) | Out-Null
New-Item -ItemType Directory -Force -Path (Split-Path $test) | Out-Null
Copy-Item 'PATCH/app/Services/ProspectDuplicateService.php' $svc
Copy-Item 'PATCH/tests/Unit/ProspectDuplicateServiceTest.php' $test -Force
php -l $svc
if ($LASTEXITCODE -ne 0) { throw 'PHP service syntax error' }
php -l .\APPLY_PHASE6C.php
if ($LASTEXITCODE -ne 0) { throw 'PHP installer syntax error' }
php .\APPLY_PHASE6C.php
if ($LASTEXITCODE -ne 0) { throw 'Safe patch failed. See message.' }
php -l .\app\Http\Controllers\ProspectController.php
if ($LASTEXITCODE -ne 0) { throw 'PHP controller syntax error. Restore from backup.' }
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'Cache cleanup failed' }
php artisan test --filter=ProspectDuplicateServiceTest
if ($LASTEXITCODE -ne 0) { throw 'Unit tests failed' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Blade cache failed' }
Write-Host 'Phase 6C installed. No CRM records deleted. Refresh /prospects with Ctrl+F5.' -ForegroundColor Green

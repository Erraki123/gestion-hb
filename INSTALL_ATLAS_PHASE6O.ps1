$ErrorActionPreference = 'Stop'
if (!(Test-Path '.\artisan')) { throw 'Run from Laravel project root.' }
if (!(Test-Path '.\app\Services\AtlasContactReviewService.php')) { throw 'Phase 6N must be installed first.' }
if (!(Test-Path '.\storage\app\prospect-imports\atlas-contact-review.csv')) { throw 'Phase 6M contact matching must be completed first.' }
$phpFiles = @(
 '.\APPLY_ATLAS_PHASE6O.php',
 '.\PATCH_ATLAS_6O\app\Services\AtlasContactDecisionService.php',
 '.\PATCH_ATLAS_6O\app\Http\Controllers\Admin\AtlasContactDecisionController.php',
 '.\PATCH_ATLAS_6O\database\migrations\2026_11_15_140000_create_atlas_contact_decisions_table.php',
 '.\PATCH_ATLAS_6O\routes\atlas_contact_phase6o.php'
)
foreach ($file in $phpFiles) { php -l $file; if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $file" } }
php .\APPLY_ATLAS_PHASE6O.php
if ($LASTEXITCODE -ne 0) { throw 'Patch not applied. Check error above.' }
php artisan test --filter=AtlasContactDecisionServiceTest
if ($LASTEXITCODE -ne 0) { throw 'Tests failed. Restore backed-up files before retrying.' }
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'View clear failed.' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Blade compile failed. Restore backed-up view.' }
php artisan route:list --name=admin.atlas.contacts.decision
if ($LASTEXITCODE -ne 0) { throw 'Route check failed.' }
Write-Host 'Tests and Blade passed. Applying isolated contact review migration...' -ForegroundColor Cyan
php artisan migrate --path=database/migrations/2026_11_15_140000_create_atlas_contact_decisions_table.php --force
if ($LASTEXITCODE -ne 0) { throw 'Migration failed. Check the database before reviewing contacts.' }
Write-Host 'ATLAS Phase 6O installed locally. No contacts copied into CRM; no messages sent.' -ForegroundColor Green
